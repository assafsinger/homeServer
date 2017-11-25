use File::Copy;

my $found = false;
my $filename = 'getWeather.html';
my $tempFile = '.tempWeather.html';

open(FILE, ">$tempFile") or die "Cannot open file";
foreach my $i (0..5) {
	my $output = `curl -s "https://query.yahooapis.com/v1/public/yql?appid=1PriZ57V34EN0O20.7R.friGXkG80jBFw4ZF7BUxG_iWQU89TontMMWnJd6wNDPynRjy&q=select%20item.condition%20from%20weather.forecast%20where%20woeid%20%3D%201968212%20and%20u%3D'c'&format=json&env=store%3A%2F%2Fdatatables.org%2Falltableswithkeys"`;
	if($output=~/temp\":\"(\d+)".+text\":\"([^\"]+)/){
		$found = true;
		printf FILE "%-20s", $1."*C ".$2;
		last;
	}
}
if (!found){
   print FILE "API data missing    ";
}
close(FILE);
move($tempFile, $filename);

#curl -s "https://query.yahooapis.com/v1/public/yql?appid=1PriZ57V34EN0O20.7R.friGXkG80jBFw4ZF7BUxG_iWQU89TontMMWnJd6wNDPynRjy&q=select%20item.condition%20from%20weather.forecast%20where%20woeid%20%3D%201968212%20and%20u%3D'c'&format=json&env=store%3A%2F%2Fdatatables.org%2Falltableswithkeys"|perl -ne 'my $found = false;foreach my $i (0..5) {if(/temp\":\"(\d+)".+text\":\"([^\"]+)/){$found = true; print("T:".$1."*C ".$2);break;}}';
