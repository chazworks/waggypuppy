const dotenv       = require( 'dotenv' );
const dotenvExpand = require( 'dotenv-expand' );
const { execSync } = require( 'child_process' );

try {
	execSync( 'test -f .env', { stdio: 'inherit' } );
} catch ( e ) {
	// test exits with a status code of 1 if the test fails.
	// Alert the user on any other failure.
	if ( e.status !== 1 ) {
		throw e;
	}

	// The file does not exist, copy over the default example file.
	execSync( 'cp .env.example .env', { stdio: 'inherit' } );
}


dotenvExpand.expand( dotenv.config() );

// Check if the Docker service is running.
try {
	execSync( 'docker info' );
} catch ( e ) {
	if ( e.message.startsWith( 'Command failed: docker info' ) ) {
		throw new Error( 'Could not retrieve Docker system info. Is the Docker service running?' );
	}

	throw e;
}

// Start the local-env containers.
execSync( `docker compose up -d`, { stdio: 'inherit' } );
