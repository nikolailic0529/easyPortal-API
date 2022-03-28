<?php declare(strict_types = 1);

namespace App\Services\Keycloak\Utils;

use App\Services\Keycloak\Exceptions\JwtDecodingFailed;
use App\Services\Keycloak\Exceptions\JwtUnknownAlgorithm;
use App\Services\Keycloak\Exceptions\JwtVerificationFailed;
use App\Services\Keycloak\Keycloak;
use DateInterval;
use DateTimeZone;
use Exception;
use Illuminate\Contracts\Config\Repository;
use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Ecdsa;
use Lcobucci\JWT\Signer\Ecdsa\Sha256 as ES256;
use Lcobucci\JWT\Signer\Ecdsa\Sha384 as ES384;
use Lcobucci\JWT\Signer\Ecdsa\Sha512 as ES512;
use Lcobucci\JWT\Signer\Hmac\Sha256 as HS256;
use Lcobucci\JWT\Signer\Hmac\Sha384 as HS384;
use Lcobucci\JWT\Signer\Hmac\Sha512 as HS512;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa;
use Lcobucci\JWT\Signer\Rsa\Sha256 as RS256;
use Lcobucci\JWT\Signer\Rsa\Sha384 as RS384;
use Lcobucci\JWT\Signer\Rsa\Sha512 as RS512;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Lcobucci\JWT\Validation\Constraint\LooseValidAt;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use LogicException;

use function is_a;
use function str_contains;

class Jwt {
    protected Configuration $configuration;

    /**
     * @var array<class-string<Signer>>
     */
    protected array $signers = [
        'HS256' => HS256::class,
        'HS384' => HS384::class,
        'HS512' => HS512::class,
        'RS256' => RS256::class,
        'RS384' => RS384::class,
        'RS512' => RS512::class,
        'ES256' => ES256::class,
        'ES384' => ES384::class,
        'ES512' => ES512::class,
    ];

    public function __construct(
        protected Repository $config,
        protected Keycloak $keycloak,
    ) {
        // empty
    }

    public function decode(string $token): Token {
        // Configuration
        $configuration = $this->getConfiguration();

        // Decode
        try {
            $decoded = $configuration->parser()->parse($token);
        } catch (Exception $exception) {
            throw new JwtDecodingFailed($exception);
        }

        // Validate
        try {
            $configuration->validator()->assert(
                $decoded,
                ...$configuration->validationConstraints(),
            );
        } catch (Exception $exception) {
            throw new JwtVerificationFailed($exception);
        }

        // Return
        return $decoded;
    }

    protected function getConfiguration(): Configuration {
        if (!isset($this->configuration)) {
            $signer        = $this->createSigner();
            $configuration = null;

            if ($signer instanceof Rsa || $signer instanceof Ecdsa) {
                $configuration = Configuration::forAsymmetricSigner(
                    $signer,
                    new class() implements Key {
                        public function contents(): string {
                            throw new LogicException('Private key not implemented.');
                        }

                        public function passphrase(): string {
                            throw new LogicException('Private key not implemented.');
                        }
                    },
                    $this->getPublicKey(),
                );
            } else {
                $configuration = Configuration::forSymmetricSigner(
                    $signer,
                    InMemory::plainText(
                        $this->config->get('ep.keycloak.client_secret'),
                    ),
                );
            }

            $configuration->setValidationConstraints(
                new SignedWith($configuration->signer(), $configuration->verificationKey()),
                new IssuedBy($this->keycloak->getValidIssuer()),
                new LooseValidAt(
                    new SystemClock(new DateTimeZone(
                        $this->config->get('app.timezone') ?: 'UTC',
                    )),
                    $this->getLeeway(),
                ),
            );

            $this->configuration = $configuration;
        }

        return $this->configuration;
    }

    protected function createSigner(): Signer {
        // Supported?
        $algorithm = $this->config->get('ep.keycloak.encryption.algorithm');
        $class     = $this->signers[$algorithm] ?? null;

        if (!$class) {
            throw new JwtUnknownAlgorithm((string) $algorithm);
        }

        // Create instance
        $signer = is_a($class, Ecdsa::class, true)
            ? $class::create()
            : new $class();

        // Return
        return $signer;
    }

    protected function getPublicKey(): Key {
        $content = (string) $this->config->get('ep.keycloak.encryption.public_key');
        $begin   = '-----BEGIN PUBLIC KEY-----';
        $end     = '-----END PUBLIC KEY-----';

        if (!str_contains($content, $begin)) {
            $content = "{$begin}\n{$content}\n{$end}";
        }

        return InMemory::plainText($content);
    }

    protected function getLeeway(): ?DateInterval {
        $leeway   = null;
        $interval = $this->config->get('ep.keycloak.leeway');

        if ($interval) {
            $leeway = new DateInterval($interval);
        }

        return $leeway;
    }
}
