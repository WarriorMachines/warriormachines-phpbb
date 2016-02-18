'use strict';

var gulp = require('gulp');
var sass = require('gulp-sass');

gulp.task('default', ['sass'], function () {

});

gulp.task('sass', function () {
    return gulp.src('./styles/warriormachines_2016/theme/sass/app.scss')
        .pipe(sass().on('error', sass.logError))
        .pipe(gulp.dest('./styles/warriormachines_2016/theme/gulp-generated/css'));
});

gulp.task('fonts', function() {
    gulp.src('./node_modules/font-awesome/fonts/**/*.{eot,svg,ttf,woff,woff2}')
        .pipe(gulp.dest('./styles/warriormachines_2016/theme/gulp-generated/fonts'));
});

gulp.task('sass:watch', function () {
    gulp.watch('./styles/warriormachines_2016/theme/sass/app.scss', ['sass']);
});
