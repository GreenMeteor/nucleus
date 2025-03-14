# Nucleus Module

## Description

This HumHub module allows administrators to easily install custom core modules directly from GitHub. It provides a simple web interface to download, install, and run migrations for core modules that should be placed in the `/protected/humhub/modules` directory.

## Features

- Download core modules directly from GitHub repositories
- Install modules to the correct core modules directory
- Automatically run migrations if available
- Backup existing modules before overwriting
- Simple admin interface

## Installation

1. Download the module archive from the [releases page](https://github.com/GreenMeteor/humhub-nucleus/releases)
2. Extract the module to your HumHub `protected/modules` directory
3. Go to Admin > Modules and activate the "Nucleus" module
4. Use the module via Admin > Modules > Nucleus

## Usage

1. Enter the GitHub URL of the core module you want to install
2. Optionally specify a branch (defaults to "master")
3. Click "Install" to download and install the module
4. The module will be installed in the `/protected/humhub/modules` directory
5. Any migrations included with the module will be automatically applied

## Requirements

- HumHub 1.16+
- PHP 8.1+
- Admin permissions to install modules

## Security Notes

- Only install modules from trusted sources
- Installing modules can potentially harm your installation if they contain malicious code
- A backup of any existing module with the same name will be created before installation
