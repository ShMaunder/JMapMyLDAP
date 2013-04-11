#!/usr/bin/perl
use strict;
use warnings;

# Convert users.csv to ldif and XML - bit hacky but who cares
# CSV should be tab delimited and contain a header row

my $xmlFile = '/tmp/jmml_users.xml';
my $ldifFile = '/tmp/jmml_users.ldif';
my $file = 'users.csv';

print "Attempting to open CSV file $file\n";

open (my $data, '<', $file) or die "Failed to open CSV";

# Get the header line out of the CSV
my @header = split "\t", <$data>;

my $ldif = "";
my $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
$xml .= "<configs>\n";
$xml .= "\t<config id=\"100\" comment=\"List of binding users\">\n";
$xml .= "\t\t<admin username=\"admin\" password=\"shmanic.com\" dn=\"cn=admin,dc=shmanic,dc=net\" />\n";

# Loop around each record
while (my $line = <$data>)
{
	next if (1..1);
	chomp $line;

	my @fields = split "\t", $line;

	$xml .= "\t\t<standard username=\"" . lc($fields[4]) . "\" password=\"$fields[11]\" dn=\"$fields[0]\">\n";

	$ldif .= "# LDAP User :: $fields[4]:$fields[11] \n";
	$ldif .= "dn: $fields[0]\n";
	$ldif .= "changetype: add\n";

	# Loop around each field in the record
	my $i = 0;
	for ($i = 0; $i < scalar (@header); $i++)
	{
		chomp $header[$i];
		next if (!$header[$i]);
		chomp $fields[$i];
		$xml .= "\t\t\t<$header[$i]>$fields[$i]</$header[$i]>\n";
		next if ($i == 0);
		$ldif .= "$header[$i]: $fields[$i]\n";

	}

	$xml .= "\t\t</standard>\n";
	$ldif .= "\n\n";

	print "Parsed $fields[4]\n";
}

close $file;

$xml .= "\t</config>\n";
$xml .= "</configs>\n";

print "\nWriting XML file\n";
open (my $xf, '>', $xmlFile);
print $xf $xml;
close $xf;
print "Successfully written $xmlFile\n";

print "\nWriting LDIF file\n";
open (my $lf, '>', $ldifFile);
print $lf $ldif;
close $lf;
print "Successfully written $ldifFile\n";

#print $ldif;
#print $xml;

