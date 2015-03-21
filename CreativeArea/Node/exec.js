function override(stream) {
	var write = stream.write;
	var buffers = [];
	stream.write = function(chunk, encoding, callback) {
		if (!chunk instanceof Buffer) {
			chunk = new Buffer(chunk, encoding);
		}
		buffers.push(chunk);
		if (callback) {
			callback();
		}
	};
	return {
		write: function() {
			return write.apply( stream, arguments );
		},
		getContent: function() {
			return Buffer.concat(buffers) + "";
		}
	};
}

var stdout = override(process.stdout);
var stderr = override(process.stderr);

var output = {};

process.on("uncaughtException", function(exception) {
	output.exception = Object.getOwnPropertyNames(exception).reduce(function(object, key) {
		object[key] = exception[key];
		return object;
	}, {});
});

process.on("exit", function() {
	output.stdout = stdout.getContent();
	output.stderr = stderr.getContent();
	stdout.write(JSON.stringify(output));
});

var targetScript = process.argv[ 2 ];

process.argv.splice(2, 1);

require(targetScript);
