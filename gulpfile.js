var gulp = require('gulp');

gulp.task('default', function() {
    gulp.src('./node_modules/font-awesome/fonts/**/*.{eot,svg,ttf,woff,woff2}')
        .pipe(gulp.dest('./styles/warriormachines_2016/theme/gulp-generated/fonts'));
});