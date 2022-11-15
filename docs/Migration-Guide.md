# Migration Guide

## See also

* [Upgrade](./Upgrade.md)

## From [v12.0.0](https://github.com/fakharanwar/easyPortal-API/releases/tag/v12.0.0)

### Composer should be updated to >=2.4.4

```shell
composer self-update
```

### Elasticsearch should be updated to >= 8.5.0

Please see [elasticsearch web-site](https://www.elastic.co/guide/en/elasticsearch/reference/current/setup-upgrade.html) for full instructions.

#### Upgrade to [7.17](https://www.elastic.co/guide/en/elasticsearch/reference/7.17/rpm.html)

```shell
sudo systemctl stop elasticsearch.service

sudo yum install --enablerepo=elasticsearch elasticsearch

sudo systemctl daemon-reload
sudo systemctl enable elasticsearch.service
sudo systemctl start elasticsearch.service
```

#### Upgrade to [8.5+](https://www.elastic.co/guide/en/elastic-stack/8.5/upgrading-elasticsearch.html)

```shell
sudo tee -a /etc/yum.repos.d/elasticsearch.repo > /dev/null <<EOT
[elasticsearch]
name=Elasticsearch repository for 8.x packages
baseurl=https://artifacts.elastic.co/packages/8.x/yum
gpgcheck=1
gpgkey=https://artifacts.elastic.co/GPG-KEY-elasticsearch
enabled=0
autorefresh=1
type=rpm-md
EOT

sudo systemctl stop elasticsearch.service

sudo yum install --enablerepo=elasticsearch elasticsearch

sudo systemctl daemon-reload
sudo systemctl enable elasticsearch.service
sudo systemctl start elasticsearch.service
```
