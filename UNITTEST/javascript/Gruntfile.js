// Skeletal gruntfile just runs the tests from the command-line
//      grunt test-ci
//
module.exports = function (grunt) {
  'use strict';

  require('matchdep').filterDev('grunt-*').forEach(grunt.loadNpmTasks);

  grunt.initConfig({
    pkg: grunt.file.readJSON('package.json'),

    connect: {
      server: {
        options: {
          port: 8899,
          base: '../../'
        }
      }
    },

    mocha: {
      all: {},
      options: {
        urls: ['http://localhost:8899/UNITTEST/javascript/tests.html'],
        run: true,
	reporter: 'XUnit'
      }
    }
  });

  grunt.registerTask('test', ['connect', 'mocha']);
  grunt.registerTask('test-server', ['connect:server:keepalive'])
  grunt.loadNpmTasks('grunt-mocha');
};
