# CMS MCP Plugin (Experimental)

[![Latest Stable](https://img.shields.io/packagist/v/softspring/cms-mcp-plugin?label=stable&style=flat-square)](https://github.com/softspring/cms-mcp-plugin/releases)
[![Latest Unstable](https://img.shields.io/packagist/v/softspring/cms-mcp-plugin?label=unstable&style=flat-square&include_prereleases)](https://github.com/softspring/cms-mcp-plugin/releases)
[![License](https://img.shields.io/packagist/l/softspring/cms-mcp-plugin?style=flat-square)](https://github.com/softspring/cms-mcp-plugin/blob/6.0/LICENSE)
[![PHP Version](https://img.shields.io/packagist/dependency-v/softspring/cms-mcp-plugin/php?style=flat-square)](https://github.com/softspring/cms-mcp-plugin/blob/6.0/composer.json)
[![Downloads](https://img.shields.io/packagist/dt/softspring/cms-mcp-plugin?style=flat-square)](https://packagist.org/packages/softspring/cms-mcp-plugin)
[![CI](https://img.shields.io/github/actions/workflow/status/softspring/cms-mcp-plugin/ci.yml?branch=6.0&style=flat-square&label=CI)](https://github.com/softspring/cms-mcp-plugin/actions/workflows/ci.yml)
[![Coverage](https://img.shields.io/codecov/c/github/softspring/cms-mcp-plugin?branch=6.0&style=flat-square)](https://app.codecov.io/gh/softspring/cms-mcp-plugin/tree/6.0)

> **Experimental package:** this plugin is in active development and its configuration, tool contracts, permissions, and extension points may change before a stable release.

`softspring/cms-mcp-plugin` provides the base package for exposing controlled Armonic CMS capabilities through MCP server tools.

The current package defines the Symfony CMS plugin entry point and the maintenance baseline. Functional MCP tools will be added on top of this base.

## Installation

```bash
composer require softspring/cms-mcp-plugin:^6.0@dev
```

The plugin requires `softspring/cms-bundle`.

Register the bundle if Symfony Flex does not do it automatically:

```php
// config/bundles.php
return [
    Softspring\CmsMcpPlugin\SfsCmsMcpPlugin::class => ['all' => true],
];
```

## Usage

This first version only provides the package and bundle foundation. It does not expose MCP tools yet.

Future MCP tools should be explicit, permission-aware, and focused on safe CMS operations that can be used by local or remote agents.

## Features

See [FEATURES.md](FEATURES.md) for the functional scope of this package.

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md).

[Report issues](https://github.com/softspring/cms-mcp-plugin/issues) and [send Pull Requests](https://github.com/softspring/cms-mcp-plugin/pulls)

## Security

See [SECURITY.md](SECURITY.md).

## License

This package is free and released under the [AGPL-3.0 license](LICENSE).
