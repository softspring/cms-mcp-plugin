# CMS MCP Plugin Features

Functional definition for `softspring/cms-mcp-plugin`.

This package provides the base for MCP server tools that expose controlled Armonic CMS capabilities.

## Purpose

- Provide a Symfony CMS plugin entry point for MCP integrations.
- Expose future CMS tools through explicit, reviewable contracts.
- Keep MCP access permission-aware and suitable for agent-driven workflows.
- Provide a stable maintenance baseline before functional tools are added.

## Main Features

- Symfony bundle class for registering the plugin in Armonic CMS projects.
- Composer package metadata for the `6.0` line.
- Standard QA scripts for code style, static analysis, unit tests, and dependency compatibility.
- CI workflow for regular and lowest supported dependency sets.

## Expected Usage

- Install it in a Symfony project that already uses `softspring/cms-bundle`.
- Register `SfsCmsMcpPlugin` as a Symfony bundle when Flex does not do it automatically.
- Add MCP server tools incrementally behind explicit service contracts.
- Validate every tool with the package test workflow before release.

## Extension Points

- Add services under the plugin namespace for MCP tool implementations.
- Register tool services through Symfony dependency injection when the first MCP server layer is introduced.
- Keep tool inputs and outputs structured so agents can call them predictably.
- Add project-level permission checks before exposing CMS mutations.

## Current Limits

- The package does not expose MCP tools yet.
- The package does not ship routes, controllers, commands, templates, or frontend assets.
- Permission and transport details are intentionally left for the first functional implementation.
