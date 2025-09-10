const js = require("@eslint/js");
const babel = require("@babel/eslint-plugin");
const jsdoc = require("eslint-plugin-jsdoc");
const promise = require("eslint-plugin-promise");
const globals = require("globals");

module.exports = [
  // Ignore (replaces .eslintignore)
  {
    ignores: ["node_modules/**", "vendor/**", "**/amd/build/**", "**/*.min.js"],
  },

  // Base recommended
  js.configs.recommended,

  // Project rules (Moodle-style)
  {
    files: ["src/amd/src/**/*.js"],
    languageOptions: {
      ecmaVersion: 2021,
      sourceType: "script",
      globals: {
        ...globals.browser,
        ...globals.jquery,
        define: "readonly",
        require: "readonly",
      },
    },
    linterOptions: {
      reportUnusedDisableDirectives: "error",
    },
    plugins: { babel, jsdoc, promise },
    rules: {
      // Style checks Moodle flagged
      curly: ["error", "all"],
      "brace-style": ["error", "1tbs", { allowSingleLine: false }],
      "block-spacing": ["error", "always"],
      "space-before-function-paren": [
        "error",
        { anonymous: "never", named: "never", asyncArrow: "always" },
      ],
      "object-curly-spacing": ["error", "never"],
      "babel/object-curly-spacing": ["error", "never"],
      "spaced-comment": ["error", "always"],
      "capitalized-comments": [
        "error",
        "always",
        { ignoreInlineComments: true, ignoreConsecutiveComments: true },
      ],

      // Hygiene
      "no-console": "error",
      "no-empty-function": "error",
      "no-unused-vars": ["error", { args: "none", ignoreRestSiblings: true }],

      // Promises
      "promise/always-return": "warn",

      // JSDoc formatting + types
      "jsdoc/require-jsdoc": [
        "warn",
        {
          publicOnly: false,
          require: { FunctionDeclaration: true, MethodDefinition: true },
        },
      ],
      "jsdoc/require-param": "error",
      "jsdoc/require-param-type": "error",
      "jsdoc/check-alignment": "error",
      "jsdoc/require-returns": "off",
    },
  },
];
