# CMS MCP Plugin (Experimental)

[![Latest Stable](https://img.shields.io/packagist/v/softspring/cms-mcp-plugin?label=stable&style=flat-square)](https://github.com/softspring/cms-mcp-plugin/releases)
[![Latest Unstable](https://img.shields.io/packagist/v/softspring/cms-mcp-plugin?label=unstable&style=flat-square&include_prereleases)](https://github.com/softspring/cms-mcp-plugin/releases)
[![License](https://img.shields.io/packagist/l/softspring/cms-mcp-plugin?style=flat-square)](https://github.com/softspring/cms-mcp-plugin/blob/6.0/LICENSE)
[![PHP Version](https://img.shields.io/packagist/dependency-v/softspring/cms-mcp-plugin/php?style=flat-square)](https://github.com/softspring/cms-mcp-plugin/blob/6.0/composer.json)
[![Downloads](https://img.shields.io/packagist/dt/softspring/cms-mcp-plugin?style=flat-square)](https://packagist.org/packages/softspring/cms-mcp-plugin)
[![CI](https://img.shields.io/github/actions/workflow/status/softspring/cms-mcp-plugin/ci.yml?branch=6.0&style=flat-square&label=CI)](https://github.com/softspring/cms-mcp-plugin/actions/workflows/ci.yml)
[![Coverage](https://img.shields.io/codecov/c/github/softspring/cms-mcp-plugin?branch=6.0&style=flat-square)](https://app.codecov.io/gh/softspring/cms-mcp-plugin/tree/6.0)

> **Experimental package:** this plugin is in active development and its configuration, tool contracts, permissions, and extension points may change before a stable release.

`softspring/cms-mcp-plugin` exposes controlled Armonic CMS capabilities through MCP server tools.

The current package provides read-only CMS context tools for project configuration, site metadata, published content, internal links, menus, optional analytics, media image type requirements, existing media image search, and detailed media image context.

## Installation

```bash
composer require softspring/cms-mcp-plugin:^6.0@dev
```

The plugin requires `softspring/cms-bundle`, `softspring/media-bundle`, and `symfony/mcp-bundle`.

Register the bundle if Symfony Flex does not do it automatically:

```php
// config/bundles.php
return [
    Softspring\CmsMcpPlugin\SfsCmsMcpPlugin::class => ['all' => true],
];
```

## Usage

This plugin registers read-only MCP tools under the `sfs_cms_` prefix.

Available tools include:

- `sfs_cms_sites_list`
- `sfs_cms_sites_get_context`
- `sfs_cms_configuration_get_context`
- `sfs_cms_analytics_get_site_metrics`
- `sfs_cms_analytics_query_pages`
- `sfs_cms_contents_search_published`
- `sfs_cms_contents_get_published`
- `sfs_cms_routes_find_internal_links`
- `sfs_cms_menus_get_context`
- `sfs_cms_media_images_list_types`
- `sfs_cms_media_images_search`
- `sfs_cms_media_images_get_context`

Tools should remain explicit, permission-aware, and focused on safe CMS operations that can be used by local or remote agents.

Analytics tools are provider-agnostic. The MCP plugin consumes the statistics API exposed by `softspring/cms-analytics-plugin` when that plugin is installed and configured. Provider-specific integrations such as Plausible, GA4 or project-specific services belong in `cms-analytics-plugin`, not in this MCP package.

## Shared Serialization

The MCP tools delegate CMS serialization to shared services under `Softspring\CmsBundle\Serialization`.

These shared serializers are intentionally not tied to MCP attributes, tool names, or MCP sessions. Future CMS API packages, CLI commands and assistant integrations should reuse this layer instead of depending on `Softspring\CmsMcpPlugin\Mcp\*` tool classes directly.

## Features

See [FEATURES.md](FEATURES.md) for the functional scope of this package.

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md).

[Report issues](https://github.com/softspring/cms-mcp-plugin/issues) and [send Pull Requests](https://github.com/softspring/cms-mcp-plugin/pulls)

## Security

See [SECURITY.md](SECURITY.md).

## License

This package is free and released under the [AGPL-3.0 license](LICENSE).
