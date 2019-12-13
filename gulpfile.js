const gulp = require('gulp');
const less = require('gulp-less');
const autoprefix = require('gulp-autoprefixer');
const rename = require('gulp-rename');
const minifyCSS = require('gulp-minify-css');

gulp.task('less', () => {
  return gulp.src('assets/src/dev-debug.less')
    .pipe(less()).on('error', (err) => {
      console.warn(err.message);
    })
    .pipe(autoprefix('last 2 versions'))
    .pipe(rename('./dev-debug.css'))
    .pipe(minifyCSS())
    .pipe(rename('./dev-debug.min.css'))
    .pipe(gulp.dest('assets/dist'))
});

gulp.task('default', gulp.parallel('less'));
