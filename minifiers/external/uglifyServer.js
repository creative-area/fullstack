"use strict";

var http = require("http");
var minify = require("uglify-js").minify;


http.createServer(function(request, response) {
	response.writeHead(200, {
		"Content-Type": "application/json"
	});
	if (request.method == "POST") {
		var body = "";
		request.on("data", function( data ) {
			body += data;
		});
		request.on("end", function() {
			var result;
			try {
				result = {
					min: minify(body, {
						fromString: true
					}).code
				};
			} catch( e ) {
				result = {
					error: e.message
				};
			}
			response.end(JSON.stringify(result));
		});
	} else {
		response.end("null");
	}
}).listen(9615);
