module.exports = function(grunt) {
  grunt.initConfig({
    pkg: grunt.file.readJSON('package.json'),
    watch: {
      options: {
        atBegin: true
      },
      scss: {
        files: ['scss/*.scss', 'scss/bootstrap/*.scss'],
        tasks: ['sass']
      },
      js: {
        files: ['assets/js/mlinvoice.js', 'assets/js/mlinvoice-form.js', 'assets/js/mlinvoice-search.js'],
        tasks: ['uglify']
      }
    },
    uglify: {
      options: {
        banner: '/*! <%= pkg.name %> <%= grunt.template.today("yyyy-mm-dd") %> */\n',
        sourceMap: true
      },
      build: {
        files: {
          'assets/js/mlinvoice.min.js': [
            'assets/js/mlinvoice.js',
            'assets/js/mlinvoice-form.js',
            'assets/js/mlinvoice-search.js',
            'assets/js/mlinvoice-theme.js',
          ]
        }
      }
    },
    sass: {
      dist: {
        options: {
          style: 'compressed'
        },
        files: {
          'assets/css/style.css': 'scss/style.scss'
        }
      }
    }
  });

  grunt.loadNpmTasks('grunt-contrib-uglify');

  grunt.loadNpmTasks('grunt-contrib-sass');

  grunt.loadNpmTasks('grunt-contrib-watch');
};
