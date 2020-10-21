#!/usr/bin/perl -w

use strict;

use Digest::MD5 qw(md5_hex);
use Getopt::Long;
use Pod::Usage;

my ($in, $out);

GetOptions(
    "in|i=s" => \$in,
    "out|o=s" => \$out,
) or pod2usage(2);

pod2usage(2) unless ($in and $out);

my $email_column;
my $lines = 0;

open("FILE_IN", $in);
open("FILE_OUT", ">$out");

while(<FILE_IN>) {
    $lines++;

    my @row = parse($_);

    unless (defined($email_column)) {
	foreach my $i (0..$#row) {
	    if ($row[$i] =~ /.\@.+\..+/) {
		$email_column = $i;
		print STDERR "Found emails in column $i\n";
		last;
	    }
	}
    }

    if ($lines > 2 and !defined($email_column)) {
	print STDERR "Unable to find any emails in the first two rows, exiting...";
	exit(1);
    }

    if (defined($email_column)) {
      $row[$email_column] =~ s/^\s+//; $row[$email_column] =~ s/\s+$//;
      $row[$email_column] = lc($row[$email_column]);
      $row[$email_column] = md5_hex($row[$email_column]);
    }

    print FILE_OUT join("\t", @row) . "\n";
}

close("FILE_OUT");

my $filetype;

sub parse {
    my ($line) = @_;

    $line =~ s/\r$//; $line =~ s/\n$//;
    $line =~ s/\r$//; $line =~ s/\n$//;

    if (!$filetype) {
	if ($line =~ /,/) {
	    $filetype = 'csv';
	}
	elsif ($line =~ /\t/) {
	    $filetype = 'tsv';
	}
	else {
	    $filetype = 'txt';
	}
	print STDERR "Looks like a $filetype file...";
    }

    my @row;

    if ($filetype eq 'tsv') {
	@row = split(/\t/, $line);
    } elsif ($filetype eq 'csv') {
	@row = split(/,/, $line);
    } else {
	@row = split(/\n/, $line);
    }

    return @row;
}

print STDERR "Hashed $lines lines\n";



__END__

=head1 NAME

hash - converts a list of emails into cryptographic hashes

=head1 SYNOPSIS

hash -i emails.txt -o hashes.txt

B<This program> takes a comma-delimited input file, looks for a column that
appears to contain emails in it and outputs a version of that file with
the emails hashed.

=cut
