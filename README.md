# Wwwision.Neos.DAM

Neos integration of the Digital Asset Management based on the Event Sourced Content Repository

> **Warning**
> This package is currently a proof of concept. It is subject to change, but it might never make it to an actual product!

## Usage

### Installation

Install using [composer](https://getcomposer.org):

```shell
composer require wwwision/neos-dam
```

> **Note**
> At the time of writing, a couple of required packages are not yet available on packagist
> You can download those from GitHub to your distribution folder:
> https://github.com/neos/neos-development-collection/tree/9.0/Neos.ContentRepository.Core
> https://github.com/neos/neos-development-collection/tree/9.0/Neos.ContentRepositoryRegistry
> https://github.com/neos/neos-development-collection/tree/9.0/Neos.ContentGraph.DoctrineDbalAdapter
> https://github.com/neos/neos-development-collection/tree/9.0/Neos.ContentGraph.PostgreSQLAdapter
> and install everything via `composer require wwwision/neos-dam neos/contentrepositoryregistry:@dev neos/contentgraph-doctrinedbaladapter:@dev neos/contentgraph-postgresqladapter:@dev`

Afterwards, call

```shell
./flow dam:setup
```

in order to create required database tables and root nodes
