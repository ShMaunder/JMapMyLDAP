#!/usr/bin/perl
use strict;
use warnings;

# http://www.behindthename.com/random/random.php?number=1&gender=m&surname=&randomsurname=yes&all=yes
# http://listofrandomnames.com/
my $randomNamesFile = 'randomNames.csv';

my $baseLineNamesFile = 'baseUserList.csv';

my @passchars = ("A".."Z", "a".."z", 0..9, "-", "\'");

my $uid = 10000;
my $output = "";

$output .= "dn\tobjectClass\tobjectClass\tobjectClass\tuid\tsn\tgivenName\tcn\tmail\tuidNumber\tgidNumber\tuserPassword\tloginShell\thomeDirectory\ttelephoneNumber\tdescription\t\n";

# Add Base Line Names and Fields
open (my $data, '<', $baseLineNamesFile) or die "Failed to open Names";

# Loop around each record
while (my $line = <$data>)
{
	next if (1..1);
	chomp $line;

	$output .= "$line\n";
}

close $baseLineNamesFile;

open ($data, '<', $randomNamesFile) or die "Failed to open Names";

# Loop around each record
while (my $line = <$data>)
{
        chomp $line;

        my @fields = split "\t", $line;

	$uid++;
	my $phone = "0" . (int(rand(999)) + 1000) . ' ' . (int(rand(99999)) + 100000);

	my $password;
	$password .= $passchars[rand @passchars] for 1..8;

	$output .= lc("uid=$fields[0].$fields[1]") . ",ou=People,dc=shmanic,dc=net\t";
	$output .= "inetOrgPerson\tposixAccount\tshadowAccount\t";
	$output .= lc("$fields[0].$fields[1]\t");
	$output .= "$fields[1]\t";
	$output .= "$fields[0]\t";
	$output .= "$fields[0] $fields[1]\t";
	$output .= lc("$fields[0].$fields[1]\@shmanic.net\t");
	$output .= "$uid\t";
	$output .= "1000\t";
	$output .= "$password\t";
	$output .= "/bin/bash\t";
	$output .= lc("/home/$fields[0].$fields[1]\t");
	$output .= "$phone\t";
	$output .= "$fields[2]\t";
	$output .= "\n";
}

close $randomNamesFile;

print $output;
