const developmentConfig = require( './tools/webpack/development' );
const mediaConfig = require( './tools/webpack/media' );
const packagesConfig = require( './tools/webpack/packages' );
const scriptModulesConfig = require( './tools/webpack/script-modules' );
const vendorsConfig = require( './tools/webpack/vendors' );

module.exports = function (
	env = { environment: 'production', watch: false, buildTarget: false }
) {
	if ( ! env.watch ) {
		env.watch = false;
	}

	if ( ! env.buildTarget ) {
		env.buildTarget = env.mode === 'production' ? 'build/' : 'src/';
	}

	const config = [
		...developmentConfig( env ),
		mediaConfig( env ),
		packagesConfig( env ),
		scriptModulesConfig( env ),
		...vendorsConfig( env ),
	];

	return config;
};
