/* eslint-env node */
module.exports = function (grunt) {
  require("load-grunt-tasks")(grunt);

  const SRC_DIR = "src/amd/src";
  const DEST_DIR = "src/amd/build";

  grunt.initConfig({
    uglify: {
      amd: {
        files: [
          {
            expand: true,
            cwd: SRC_DIR,
            src: ["*.js"],
            dest: DEST_DIR,
            ext: ".min.js",
          },
        ],
        options: {
          mangle: true,
          compress: true,
          output: { comments: false },
        },
      },
    },
    watch: {
      amd: {
        files: [`${SRC_DIR}/*.js`],
        tasks: ["build-amd"],
        options: { spawn: false },
      },
    },
  });

  grunt.registerTask("build-amd", ["uglify:amd"]);
  grunt.registerTask("watch-amd", ["watch:amd"]);
  grunt.registerTask("default", ["build-amd"]);
};
