<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>{{message}}</title>
	@includeMorph
	<style>
		.trace {
			color:white;
			transition:0.3s;
		}
		.trace:hover {
			color:var(--morph-primary);
		}
	</style>
</head>
<body>
	<morph name='main'>
		<table>
			<tbody>
				<tr>
					<th>ERROR: {{message}}</th>
					<th>In File: {{file}}</th>
				</tr>
				
				<tr>
					<th>On Line: {{line}}</th>
					<th><pre>{{context}}</pre></th>
				</tr>
			</tbody>
		</table>
		<a class='trace' href='#trace'><h2>Trace</h2></a>
	</morph>	

	<morph name='trace'>
		<a href='#main'><h2>Back</h2></a>
		<table>
			<thead>
				<tr>
					<th>File</th>
					<th>Line</th>
					<th>context</th>
				</tr>
			</thead>
			<tbody>
			@loop($traces, 'trace')
				<tr>
					<td>{{trace['file']}}</td>
					<td>{{trace['line']}}</td>
					<td><pre>{{trace['context']}}</pre></td>
				</tr>
			@endloop
			</tbody>
		</table>
	</morph>
</body>
</html>
