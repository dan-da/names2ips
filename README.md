# names2ips

A simple command line (CLI) program that queries DNS and generates a report of
IP addresses given a list of hostnames. It can return IPs as integer, hex, or
standard dot notation. It is possible to specify endian-ness of integer and hex
values.

Many output formats are supported
   json|csv|text|textcompact|code|printr


# Examples

## Query Bitcoin Seed Node IPs in code format as big-endian hex values.

This format is used in [bitcoinj](https://github.com/bitcoinj/bitcoinj).  This exact command can be run to
provide values that can be copy/pasted into the [bitcoinj code](https://github.com/bitcoinj/bitcoinj/blob/8e00564f4e736b1bc92b7f37664387cb710b8fa2/core/src/main/java/org/bitcoinj/params/MainNetParams.java).

```
$ ./names2ips.php --hostnames=seed.bitcoin.sipa.be,dnsseed.bluematt.me,dnsseed.bitcoin.dashjr.org,seed.bitcoinstats.com,seed.bitnodes.io --format=code --ipformat=hex --endian=big
// -- seed.bitcoin.sipa.be --
0xf043fb94, 0x4706886b, 0xb40917d9, 0x8eb9b5b,  0x6407a9c0, 0x1189e1ce, 0x7d13fa17, 
0xa0aa79b9, 0xeb4de5c,  0xb28ace32, 0x618bdd52, 0x733844bc, 0x6a0cd98e, 0x91d91834, 
0xa5ab2fd5, 0xa7db7acb, 0x24db2c44, 0xbad30955, 0xe4a0c768, 0x3ccd82c3, 0xcb3b7a56, 
// -- dnsseed.bluematt.me --
0x74a42942, 0xf3018f45, 0xf906af4b, 0x337d9553, 0x3917c658, 0xbdf03d80, 0x68487283, 
0xc453fb94, 0xce464aad, 0x948219ae, 0xef218abc, 0x3bd8a5bc, 0x14cacec0, 0xc24ec0c7, 
0xe55191d9, 0x695c2918, 0x6265ba1f, 0xce6be12b, 0xbc85372d, 0xe64ca32e, 0x19eed83e, 
// -- dnsseed.bitcoin.dashjr.org --
0xa434a87a, 0x2c75172f, 0x2f36977a, 0xc1a29a51, 0x2814da36, 0x28412332, 0xc8438750, 
0x84b83534, 0x650417d9, 0xe0e5a756, 0x3e67415b, 0xc13c1752, 0x2dd96bd9, 0xc03ca51e, 
0xd5ed9ed8, 0x47a6c98a, 0x8fe0cf34, 0x8e32592f, 0x12caedc0, 0xa63ab843, 0x3fd4d376, 
// -- seed.bitcoinstats.com --
0x9b906db9, 0x7239354d, 0xcecc1c2e, 0x6148a452, 0xf0817ad5, 0x778441b9, 0xc150a2d9, 
0x22e80eb9, 0x3ccd82c3, 0x1b5a10c3, 0xe8649fd4, 0x3c6d695b, 0x20527452, 0x630ca857, 
0x21455c,   0x551e3b34, 0x4b761fb0, 0x376eee4e, 0x7f5d952,  0x1dfa3925, 0x2df10c52, 
// -- seed.bitnodes.io --
0x12ce6018, 0x17c77025, 0x2f78652e, 0xac96f40,  0x5b28ef4d, 0x9b60c14e, 0xbdb32d56, 
0x2783dc5b, 0x8cf0735d, 0xd19bfc6d, 0xcf00d972, 0x158af074, 0xb2ba368e, 0x97700290, 
0x7dc502ae, 0x76385cb1, 0x46bb31b2, 0x2922bb3,  0xb73119b9, 0x8efd5fc1, 0x1d81a9c1,
```

## Get some yahoo IPs broken out by domain.

```
$ ./names2ips.php --hostnames=yahoo.com,mail.yahoo.com,my.yahoo.com,maps.yahoo.com --format=text
-- yahoo.com --
206.190.36.45    
98.138.253.109   
98.139.183.24    

-- mail.yahoo.com --
98.136.189.41    

-- my.yahoo.com --
206.190.36.45    
206.190.36.105   

-- maps.yahoo.com --
98.137.250.95  
```

### Now in a more compact format.

```
./names2ips.php --hostnames=yahoo.com,mail.yahoo.com,my.yahoo.com,maps.yahoo.com --format=textcompact
-- yahoo.com --
98.139.183.24    98.138.253.109   206.190.36.45    
-- mail.yahoo.com --
98.136.189.41    
-- my.yahoo.com --
206.190.36.45    206.190.36.105   
-- maps.yahoo.com --
98.137.250.95
```

### Now as a flat list

```
$ ./names2ips.php --hostnames=yahoo.com,mail.yahoo.com,my.yahoo.com,maps.yahoo.com --format=text --groupby=none
98.139.183.24    
98.138.253.109   
206.190.36.45    
98.136.189.41    
206.190.36.45    
206.190.36.105   
98.137.250.95
```
### Now in json

```
$ ./names2ips.php --hostnames=yahoo.com,mail.yahoo.com,my.yahoo.com,maps.yahoo.com --format=jsonpretty
{
    "yahoo.com": [
        "206.190.36.45",
        "98.138.253.109",
        "98.139.183.24"
    ],
    "mail.yahoo.com": [
        "98.136.189.41"
    ],
    "my.yahoo.com": [
        "206.190.36.105",
        "206.190.36.45"
    ],
    "maps.yahoo.com": [
        "98.137.250.95"
    ]
}
```

### Now in a flat json (raw) list, as integers.

```
$ ./names2ips.php --hostnames=yahoo.com,mail.yahoo.com,my.yahoo.com,maps.yahoo.com --format=json --ipformat=longint
{"yahoo.com":[1653323544,1653276013,3468567597],"mail.yahoo.com":[1653128489],"my.yahoo.com":[3468567657,3468567597],"maps.yahoo.com":[1653209695]}
```

I think you get the idea.


# Usage

$ ./names2ips.php 

   names2ips.php [options] --hostnames=<csv> | --hostnamefile=<file>

   This script generates a report of IP addresses given a list of hostnames.
   It can return IPs as integer,hex, or standard dot notation.
   It is possible to specify endian-ness of integer and hex values.

   Options:

    --hostnames=<csv>      comma separated list of bitcoin addresses
    --hostnamefile=<path>  file containing bitcoin addresses, one per line.
    --ipformat=<path>      longint|hex|dot    default=dot
    --groupby=<type>       hostname|none  default = hostname
    --format=<type>        json|csv|text|textcompact|code|printr
    --outfile=<file>       file to write report to instead of stdout.
    --endian=<type>        big|little.  used when ipformat is longint or hex.
                             default = little

