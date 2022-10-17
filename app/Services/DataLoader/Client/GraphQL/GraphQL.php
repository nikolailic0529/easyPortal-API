<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client\GraphQL;

use JsonSerializable;
use Stringable;

abstract class GraphQL implements Stringable, JsonSerializable {
    final public function __construct() {
        // empty
    }

    abstract public function getSelector(): string;

    // <editor-fold desc="JsonSerializable">
    // =========================================================================
    public function jsonSerialize(): string {
        return (string) $this;
    }
    // </editor-fold>

    // <editor-fold desc="GraphQL">
    // =========================================================================
    protected function getCompanyInfoGraphQL(): string {
        return <<<'GRAPHQL'
            id
            name
            status
            updatedAt
            companyType
            GRAPHQL;
    }

    protected function getCompanyContactPersonsGraphQL(): string {
        return <<<'GRAPHQL'
            companyContactPersons {
                phoneNumber
                name
                type
                mail
            }
            GRAPHQL;
    }

    protected function getCompanyLocationsGraphQL(): string {
        return <<<'GRAPHQL'
            locations {
                country
                countryCode
                latitude
                longitude
                city
                zip
                address
                locationType
            }
            GRAPHQL;
    }

    protected function getCompanyKpisGraphQL(): string {
        return <<<GRAPHQL
            companyKpis {
                {$this->getKpisGraphQL()}
            }
            GRAPHQL;
    }

    protected function getCompanyBrandingDataGraphQL(): string {
        return <<<'GRAPHQL'
            brandingData {
                brandingMode
                defaultLogoUrl
                defaultMainColor
                favIconUrl
                logoUrl
                mainColor
                mainImageOnTheRight
                resellerAnalyticsCode
                secondaryColor
                secondaryColorDefault
                useDefaultFavIcon
                mainHeadingText {
                    language_code
                    text
                }
                underlineText {
                    language_code
                    text
                }
            }
            GRAPHQL;
    }

    protected function getCompanyKeycloakGraphQL(): string {
        return <<<'GRAPHQL'
            keycloakName
            keycloakGroupId
            keycloakClientScopeName
            GRAPHQL;
    }

    protected function getResellerPropertiesGraphQL(): string {
        return <<<GRAPHQL
            {$this->getCompanyInfoGraphQL()}
            {$this->getCompanyContactPersonsGraphQL()}
            {$this->getCompanyLocationsGraphQL()}
            {$this->getCompanyKpisGraphQL()}
            {$this->getCompanyKeycloakGraphQL()}
            {$this->getCompanyBrandingDataGraphQL()}
            GRAPHQL;
    }

    protected function getCustomerPropertiesGraphQL(): string {
        return <<<GRAPHQL
            {$this->getCompanyInfoGraphQL()}
            {$this->getCompanyContactPersonsGraphQL()}
            {$this->getCompanyLocationsGraphQL()}
            {$this->getCompanyKpisGraphQL()}
            companyResellerKpis {
                resellerId
                {$this->getKpisGraphQL()}
            }
            GRAPHQL;
    }

    protected function getDistributorPropertiesGraphQL(): string {
        return $this->getCompanyInfoGraphQL();
    }

    protected function getAssetPropertiesGraphQL(): string {
        return <<<'GRAPHQL'
            id
            serialNumber

            assetSku
            assetSkuDescription
            assetTag
            assetType
            status
            vendor

            eolDate
            eosDate

            country
            countryCode
            latitude
            longitude
            zip
            city
            address
            address2

            customerId
            resellerId

            updatedAt

            latestContactPersons {
                phoneNumber
                name
                type
                mail
            }

            assetCoverage
            coverageStatusCheck {
                coverageStatus
                coverageStatusUpdatedAt
                coverageEntries {
                    coverageStartDate
                    coverageEndDate
                    type
                    description
                    status
                    serviceSku
                }
            }

            dataQualityScore
            activeContractQuantitySum
            GRAPHQL;
    }

    protected function getAssetDocumentsPropertiesGraphQL(): string {
        return <<<'GRAPHQL'
            assetDocument {
                startDate
                endDate
                documentNumber

                document {
                    id
                    type
                    documentNumber

                    startDate
                    endDate

                    currencyCode
                    languageCode

                    updatedAt

                    vendorSpecificFields {
                        vendor
                        groupId
                        groupDescription
                        said
                        ampId
                        sar
                    }

                    contactPersons {
                        phoneNumber
                        name
                        type
                        mail
                    }

                    customerId
                    resellerId
                    distributorId
                }

                serviceGroupSku
                serviceGroupSkuDescription
                serviceLevelSku
                serviceLevelSkuDescription
                serviceFullDescription

                customer {
                  id
                }

                reseller {
                  id
                }
            }
            GRAPHQL;
    }

    protected function getDocumentPropertiesGraphQL(): string {
        return <<<'GRAPHQL'
            id
            type
            status
            documentNumber
            startDate
            endDate
            currencyCode
            totalNetPrice
            languageCode
            updatedAt
            resellerId
            customerId
            distributorId

            vendorSpecificFields {
                vendor
                groupId
                groupDescription
                said
                ampId
                sar
            }

            contactPersons {
                phoneNumber
                name
                type
                mail
            }

            documentEntries {
                assetId
                assetProductLine
                assetProductGroupDescription
                serviceGroupSku
                serviceGroupSkuDescription
                serviceLevelSku
                serviceLevelSkuDescription
                serviceFullDescription
                startDate
                endDate
                languageCode
                currencyCode
                listPrice
                estimatedValueRenewal
                assetProductType
                environmentId
                equipmentNumber
                lineItemListPrice
                lineItemMonthlyRetailPrice
                said
                sarNumber
                pspId
                pspName
            }
            GRAPHQL;
    }

    protected function getKpisGraphQL(): string {
        return <<<'GRAPHQL'
            totalAssets
            activeAssets
            activeAssetsPercentage
            activeCustomers
            newActiveCustomers
            activeContracts
            activeContractTotalAmount
            newActiveContracts
            expiringContracts
            activeQuotes
            activeQuotesTotalAmount
            newActiveQuotes
            expiringQuotes
            expiredQuotes
            expiredContracts
            orderedQuotes
            acceptedQuotes
            requestedQuotes
            receivedQuotes
            rejectedQuotes
            awaitingQuotes
            activeAssetsOnContract
            activeAssetsOnWarranty
            activeExposedAssets
            serviceRevenueTotalAmount
            serviceRevenueTotalAmountChange
            GRAPHQL;
    }
    //</editor-fold>
}
