module.exports = function(grunt) {
  grunt.initConfig({
    pkg: grunt.file.readJSON('package.json'),
    watch: {
      options: {
        atBegin: true
      },
      scss: {
        files: 'css/*.scss',
        tasks: ['sass']
      },
      js: {
        files: ['js/mlinvoice.js', 'js/mlinvoice-form.js'],
        tasks: ['uglify']
      }
    },
    uglify: {
      options: {
        banner: '/*! <%= pkg.name %> <%= grunt.template.today("yyyy-mm-dd") %> */\n'
      },
      build: {
        files: {
          'js/mlinvoice.min.js': ['js/mlinvoice.js', 'js/mlinvoice-form.js']
        }
      }
    },
    sass: {
      dist: {
        options: {
          style: 'compressed'
        },
        files: {
          'css/style.css': 'css/style.scss',
          'css/table.css': 'css/table.scss',
        }
      }
    }
  });

  grunt.loadNpmTasks('grunt-contrib-uglify');

  grunt.loadNpmTasks('grunt-contrib-sass');

  grunt.loadNpmTasks('grunt-contrib-watch');
};
