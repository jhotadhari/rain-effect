'use strict';

module.exports = {
	all: {
		files: [{
			expand: true,
			cwd: 'src/commonJS',
			src: [
				'*.js',
			],
			dest: '<%= dest_path %>/js',
			rename: function (dst, src) {
				return dst + '/' + src.replace('.js', '.min.js');
			}
		}],

        options: {
           transform: [
           	   [ 'babelify', {presets: ['es2015']}],
           	   [ 'glslify' ],
           	   [ 'uglifyify' ],
           	   [ 'browserify-shim', {global: true}]
           ],
           browserifyOptions: {
           	   // debug: true,
           }
        },
	}
};

