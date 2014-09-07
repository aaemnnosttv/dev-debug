var gulp = require('gulp'),
  less = require('gulp-less'),
  autoprefix = require('gulp-autoprefixer'),
  sourcemaps = require('gulp-sourcemaps'),
  rename = require('gulp-rename'),
  concat = require('gulp-concat'),
  minifyCSS = require('gulp-minify-css'),
  jshint = require('gulp-jshint'),
  uglify = require('gulp-uglify'),
  livereload = require('gulp-livereload'),
  stylish = require('jshint-stylish');
  //rev = require('gulp-rev');
  //modernizr = require('gulp-modernizr');

var paths = {
  scripts: [
    'assets/dev-debug.js'
  ],
  jshint: [
    'gulpfile.js',
    'assets/*.js',
    '!assets/dev-debug.js',
    '!assets/dev-debug.min.js',
    '!assets/**/*.min-*'
  ],
  less: 'assets/dev-debug.less'
};

var destination = {
  css: 'assets',
  scripts: 'assets'
};

gulp.task('less', function() {
  return gulp.src(paths.less)
    .pipe(sourcemaps.init())
      .pipe(less()).on('error', function(err) {
        console.warn(err.message);
      })
      .pipe(autoprefix('last 2 versions', 'ie 8', 'ie 9', 'android 2.3', 'android 4', 'opera 12'))
      .pipe(rename('./dev-debug.css'))
    .pipe(sourcemaps.write())
    .pipe(gulp.dest(destination.css))
    .pipe(minifyCSS())
    .pipe(rename('./dev-debug.min.css'))
    .pipe(gulp.dest(destination.css))
    .pipe(livereload({ auto: false }));
});

gulp.task('jshint', function() {
  return gulp.src(paths.jshint)
    .pipe(jshint())
    .pipe(jshint.reporter(stylish));
});

gulp.task('js', ['jshint'], function() {
  return gulp.src(paths.scripts)
    .pipe(concat('./dev-debug.js'))
    .pipe(gulp.dest(destination.scripts))
    .pipe(uglify())
    .pipe(rename('./dev-debug.min.js'))
    .pipe(gulp.dest(destination.scripts))
    .pipe(livereload({ auto: false }));
});


gulp.task('version', function() {
  return gulp.src(['assets/dev-debug.css', 'assets/dev-debug.min.js'], { base: 'assets' })
    .pipe(rev())
    .pipe(gulp.dest('assets'))
    .pipe(rev.manifest())
    .pipe(gulp.dest('assets'));
});

gulp.task('watch', function() {
  livereload.listen();
  gulp.watch('assets/**/*.less', ['less']);
  gulp.watch('assets/**/*.js', ['jshint', 'js']);
  gulp.watch('**/*.php').on('change', function(file) {
    livereload.changed(file.path);
  });
});

gulp.task('default', ['less', 'jshint', 'js']);
gulp.task('dev', ['default','watch']);
gulp.task('build', ['less', 'jshint', 'js', 'version']);