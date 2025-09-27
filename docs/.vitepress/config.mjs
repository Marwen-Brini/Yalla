import { defineConfig } from 'vitepress'

export default defineConfig({
  title: 'Yalla CLI',
  description: 'A standalone PHP CLI framework built from scratch',
  base: '/Yalla/',

  themeConfig: {

    nav: [
      { text: 'Guide', link: '/guide/getting-started' },
      { text: 'API', link: '/api/application' },
      { text: 'Examples', link: '/examples/basic-usage' },
      {
        text: 'v1.4.0',
        items: [
          { text: 'Changelog', link: '/changelog' },
          { text: 'Contributing', link: '/contributing' },
          { text: 'License', link: 'https://github.com/marwen-brini/yalla/blob/main/LICENSE' }
        ]
      }
    ],

    sidebar: {
      '/guide/': [
        {
          text: 'Introduction',
          items: [
            { text: 'Getting Started', link: '/guide/getting-started' },
            { text: 'Installation', link: '/guide/installation' },
            { text: 'Quick Start', link: '/guide/quick-start' }
          ]
        },
        {
          text: 'Core Concepts',
          items: [
            { text: 'Commands', link: '/guide/commands' },
            { text: 'Arguments & Options', link: '/guide/arguments-options' },
            { text: 'Output Formatting', link: '/guide/output' },
            { text: 'Table Formatting', link: '/guide/tables' },
            { text: 'Input Parsing', link: '/guide/input' }
          ]
        },
        {
          text: 'Advanced Features',
          items: [
            { text: 'REPL', link: '/guide/repl' },
            { text: 'Command Scaffolding', link: '/guide/scaffolding' },
            { text: 'Testing', link: '/guide/testing' },
            { text: 'Extensions', link: '/guide/extensions' }
          ]
        }
      ],
      '/api/': [
        {
          text: 'Core Classes',
          items: [
            { text: 'Application', link: '/api/application' },
            { text: 'Command', link: '/api/command' },
            { text: 'CommandRegistry', link: '/api/command-registry' },
            { text: 'Output', link: '/api/output' },
            { text: 'Table', link: '/api/table' },
            { text: 'MigrationTable', link: '/api/migration-table' },
            { text: 'InputParser', link: '/api/input-parser' }
          ]
        },
        {
          text: 'REPL',
          items: [
            { text: 'ReplSession', link: '/api/repl-session' },
            { text: 'ReplContext', link: '/api/repl-context' },
            { text: 'ReplConfig', link: '/api/repl-config' },
            { text: 'ReplExtension', link: '/api/repl-extension' }
          ]
        },
        {
          text: 'Built-in Commands',
          items: [
            { text: 'HelpCommand', link: '/api/help-command' },
            { text: 'ListCommand', link: '/api/list-command' },
            { text: 'CreateCommandCommand', link: '/api/create-command' },
            { text: 'InitCommand', link: '/api/init-command' }
          ]
        }
      ],
      '/examples/': [
        {
          text: 'Examples',
          items: [
            { text: 'Basic Usage', link: '/examples/basic-usage' },
            { text: 'Custom Commands', link: '/examples/custom-commands' },
            { text: 'REPL Extensions', link: '/examples/repl-extensions' },
            { text: 'Complex CLI App', link: '/examples/complex-app' }
          ]
        }
      ]
    },

    socialLinks: [
      { icon: 'github', link: 'https://github.com/marwen-brini/yalla' }
    ],

    footer: {
      message: 'Released under the MIT License.',
      copyright: 'Copyright © 2025 Marwen-Brini'
    },

    search: {
      provider: 'local'
    }
  },

  markdown: {
    lineNumbers: true
  }
})