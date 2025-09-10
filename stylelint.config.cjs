module.exports = {
  extends: ['stylelint-config-standard'],
  plugins: ['@stylistic/stylelint-plugin'],
  rules: {
    // was: indentation: 4
    '@stylistic/indentation': 4
  },
  ignoreFiles: [
    'node_modules/**',
    'vendor/**'
  ]
};
