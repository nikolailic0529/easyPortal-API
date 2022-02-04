<?php declare(strict_types = 1);

namespace Tests\Data\Services\DataLoader\Importers;

class AssetsIteratorImporterDataWithDocuments extends AssetsIteratorImporterDataWithoutDocuments {
    public const DOCUMENTS = true;
    public const ASSETS    = [
        '6bad01f8-09bb-4809-bc5c-e154917dc505',
        '1e59d9e8-a7b6-4b37-a867-c2e4d2e6ccc4',
        'e39b8f88-3f4e-4eb5-9a9d-265574dc380d',
        'b294de4a-bba9-4894-a300-510fcccfbca2',
        'afe848a4-01a0-4a4b-ad0b-e29506d8a8f6',
        '7dcc2021-9081-4213-8e0c-a6c8d50ffa2d',
        '5f7d2467-43ef-4ffe-9bad-a6566b8c9bca',
        'f5ea76d0-7b8a-46f6-a195-58f25dff39cd',
        'bc0520fb-2cca-41a8-8594-e9c257a7d5e9',
        'a7a10ec5-c22d-44d2-9bbc-10de9bb04501',
        '00000000-0000-0000-0000-000000000000',
    ];
}
