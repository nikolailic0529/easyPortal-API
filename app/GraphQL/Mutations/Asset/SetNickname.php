<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Asset;

use App\Models\Asset;

class SetNickname {
    /**
     * @param array{input: array<mixed>} $args
     */
    public function __invoke(Asset $asset, array $args): Asset|bool {
        $input           = new SetNicknameInput($args['input']);
        $asset->nickname = $input->nickname;
        $asset->save();

        return $asset;
    }
}
