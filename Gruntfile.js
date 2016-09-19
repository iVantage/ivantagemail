// ---------------------------------------------
// Gruntfile.js
// ---------------------------------------------

module.exports = function(grunt) {

  var codecoverage = grunt.option('codecoverage') || false;

  // ---------------------------------------------
  // Project configuration
  // ---------------------------------------------
  grunt.initConfig({
    pkg: grunt.file.readJSON('package.json'),

    // ---------------------------------------------
    // watch
    // ---------------------------------------------
    watch: {
      test: {
        files: ['src/**/*.php', 'tests/**/*.php'],
        tasks: ['phpunit']
      }
    },

    // ---------------------------------------------
    // phpUnit
    // ---------------------------------------------
    phpunit: {
      all: {
        dir: '',
        options: {
          configuration: __dirname + '/phpunit.xml',
          coverageHtml: codecoverage ? __dirname + '/codecoverage' : false
        }
      },
      options: {
        bin: __dirname + '/vendor/bin/phpunit',
        colors: true
      }
    }
  });

  // ---------------------------------------------
  // Load plugins
  // ---------------------------------------------
  grunt.loadNpmTasks('grunt-phpunit');
  grunt.loadNpmTasks('grunt-contrib-watch');
  grunt.loadNpmTasks('grunt-ivantage-svn-release');

  // ---------------------------------------------
  // Register tasks
  // ---------------------------------------------
  grunt.registerTask('default', ['watch']);
}
