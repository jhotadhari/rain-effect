
const path = require( 'path' )
const {
	get,
	set,
} = require( 'lodash' );

const browserifyTransform = grunt => {

	grunt.hooks.addFilter( 'config.browserify', 'rain.config.browserify.transform', config => {

		const transform = get( config, ['options','transform'], [] );
		// const transformBabelifyI = transform.findIndex( tf => path.resolve( 'node_modules/babelify' ) === tf[0] );
		// if ( -1 === transformBabelifyI )
		// 	return;

		// const transformBabelify = transform[transformBabelifyI];
		// let newTransformBabelify = [...transformBabelify];
		// set( newTransformBabelify, [1,'plugins'], [
		// 	...get( transformBabelify, [1,'plugins'], [] ),
		// 	'@babel/plugin-proposal-class-properties',
		// ] );

		let newTransform = [
			...transform,
			[ 'glslify' ],
		];
		// set( newTransform, [transformBabelifyI], newTransformBabelify );

		const newConfig = { ...config, };
		set( newConfig, ['options','transform'], newTransform );

		return newConfig;
	}, 10 );

};

module.exports = browserifyTransform;
