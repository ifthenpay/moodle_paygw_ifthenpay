const js = require('@eslint/js');
const babel = require('@babel/eslint-plugin');
const jsdoc = require('eslint-plugin-jsdoc');
const promise = require('eslint-plugin-promise');
const globals = require('globals');

module.exports = [
  // Ignore (replaces .eslintignore)
  {
    ignores: [
      'node_modules/**',
      'vendor/**',
      'src/amd/build/**',
      '**/*.min.js'
    ],
  },

  // Base recommended
  js.configs.recommended,

  // Project JS (AMD in browser + jQuery)
  {
    files: ['src/amd/src/**/*.js'],
    languageOptions: {
      ecmaVersion: 2021,
      sourceType: 'script',
      globals: {
        ...globals.browser,
        ...globals.jquery,
        define: 'readonly',   // AMD
        require: 'readonly'
      }
    },
    plugins: {
      babel,
      jsdoc,
      promise
    },
    rules: {
      curly: ['error', 'all'],
      'space-before-function-paren': ['error', { anonymous: 'never', named: 'never', asyncArrow: 'always' }],
      'capitalized-comments': ['warn', 'always', { ignoreInlineComments: true, ignoreConsecutiveComments: true }],
      'spaced-comment': ['error', 'always'],
      'object-curly-spacing': ['error', 'never'],
      'babel/object-curly-spacing': ['error', 'never'],
      'no-empty-function': 'error',
      'no-unused-vars': ['error', { args: 'none', ignoreRestSiblings: true }],
      'promise/always-return': 'warn',
      'jsdoc/require-jsdoc': ['warn', {
        publicOnly: false,
        require: { FunctionDeclaration: true, MethodDefinition: true }
      }],
      'jsdoc/require-param': 'warn',
      'jsdoc/require-returns': 'off'
    }
  }
];
