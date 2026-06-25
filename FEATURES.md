# CMS MCP Plugin Features

Functional definition for `softspring/cms-mcp-plugin`.

This package provides MCP server tools that expose controlled Armonic CMS capabilities.

## Purpose

- Provide a Symfony CMS plugin entry point for MCP integrations.
- Expose CMS tools through explicit, reviewable contracts.
- Keep MCP access permission-aware and suitable for agent-driven workflows.
- Keep CMS MCP tooling separate from admin AI experiences.

## Main Features

- Symfony bundle class for registering the plugin in Armonic CMS projects.
- Read-only CMS project configuration context tool for sites, layouts, modules, content types, menus, blocks and registered plugins.
- Read-only site context tool with CMS metadata.
- Read-only published content search and detail tools.
- Read-only internal link discovery from published CMS routes.
- Read-only menu context tool with menu item trees.
- Read-only optional provider-agnostic site analytics tools, including aggregate site metrics and sortable page analytics queries.
- Read-only media image tools for image type requirements, image search, and image context.
- Shared CMS serializers for configuration, sites, published content, menus, and media images.
- Composer package metadata for the `6.0` line.
- Standard QA scripts for code style, static analysis, unit tests, and dependency compatibility.
- CI workflow for regular and lowest supported dependency sets.

## Expected Usage

- Install it in a Symfony project that already uses `softspring/cms-bundle`.
- Register `SfsCmsMcpPlugin` as a Symfony bundle when Flex does not do it automatically.
- Use the registered `sfs_cms_` MCP tools from MCP clients or AI admin integrations.
- Validate every tool with the package test workflow before release.

## Extension Points

- Add services under the plugin namespace for MCP tool implementations.
- Register tool services through Symfony dependency injection.
- Reuse `Softspring\CmsBundle\Serialization` serializers from MCP tools, API integrations, CLI commands and assistant features.
- Consume analytics through `softspring/cms-analytics-plugin`; provider implementations belong there.
- Keep tool inputs and outputs structured so agents can call them predictably.
- Add project-level permission checks before exposing CMS mutations.

## Current Limits

- The package only exposes read-only tools.
- The package does not ship routes, controllers, commands, templates, or frontend assets.
- MCP transport, server instructions, and scan directories are configured by the host application.

