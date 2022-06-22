const gulp       = require( 'gulp' ),
      browserify = require( 'browserify' ),
      buffer     = require( 'vinyl-buffer' ),
      source     = require( 'vinyl-source-stream' ),
      uglify     = require( 'gulp-uglify' );


// Build JS files.
function jsTask() {
  return browserify( { entries: ['PLUGIN/source/scripts.js'] } )
    .transform( 'babelify', { presets: [ '@babel/preset-env', '@babel/preset-react' ] } )
    .bundle()
    .pipe( source( 'scripts.min.js' ) )
    .pipe( buffer() )
    .pipe( uglify() )
    .pipe( gulp.dest( 'PLUGIN/build/js' ) );
}


gulp.task( 'default', jsTask );
