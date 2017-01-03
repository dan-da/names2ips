#!/usr/bin/env php
<?php

require_once __DIR__ . '/mylogger.class.php';

// no global scope execution past this point!
exit( main( $argv ) );


/**
 * Our main function.  It only performs top-level exception handling.
 */
function main( $argv ) {
    ini_set('memory_limit', -1 );

    $worker = new names2ips();
    try {
        return $worker->run( $argv );
    }
    catch( Exception $e ) {
        mylogger()->log_exception( $e );
        
        // print validation errors to stderr.
        if( $e->getCode() == 2 ) {
            fprintf( STDERR, $e->getMessage() . "\n\n" );
        }
        return $e->getCode() ?: 1;
    }
}

/**
 * Main App
 */
class names2ips {

    // where all the work starts and ends.
    public function run( $argv ) {
        $params = $this->get_cli_params();
        
        $rc = $this->process_cli_params( $params );
        if( $rc != 0 ) {
            return $rc;
        }
        $params = $this->get_params();
        
        $start = microtime( true );
        
        $results = $this->process_hostnames();
        $this->print_results( $results );
        
        $end = microtime(true);
        $duration = $end - $start;
        echo "\nExecution time: $duration seconds\n\n";
        
    }    

    /**
     * returns the CLI params, exactly as entered by user.
     */
    protected function get_cli_params() {
        $params = getopt( 'g', [ 'hostnames:', 'hostnamesfile:', 'outfile:', 'logfile:', 'groupby:', 'sort:', 'ipformat:', 'format:', 'endian:', 'help', 'version' ] );

        return $params;
    }

    /**
     * processes and normalizes the CLI params. adds defaults
     * and ensure each value is set.
     */
    protected function process_cli_params( $params ) {
        
        if( @$params['logfile'] ) {
            mylogger()->set_log_file( $params['logfile'] );
            mylogger()->echo_log = false;
        }
               
        $this->params = $params;
        
        $this->params['format'] = @$params['format'] ?: 'json';
        $this->params['groupby'] = @$params['groupby'] ?: 'hostname';
        $this->params['ipformat'] = @$params['ipformat'] ?: 'dot';
        $this->params['endian'] = @$params['endian'] ?: 'little';
        $this->params['sort'] = @$params['sort'] ?: 'asc';

        if( isset( $params['version'] ) ) {
            $this->print_version();
            return 2;
        }

        if( isset( $params['help'] ) ) {
            $this->print_help();
            return 1;
        }
        
        if( !isset( $params['hostnames'] ) && !isset( $params['hostnamesfile'] ) ) {
            $this->print_help();
            return 2;
        }

        return 0;
    }

    /**
     * returns the normalized CLI params, after initial processing/sanitization.
     */
    protected function get_params() {
        return $this->params;
    }

    /**
     * obtains the hostnames from user input, either via the
     *    --hostnames arg or the --hostnamesfile arg.
     */
    protected function get_hostnames() {
        // optimize retrieval.
        static $hostnames = null;
        if( $hostnames ) {
            return $hostnames;
        }
        
        $params = $this->get_params();
        
        $list = array();
        if( @$params['hostnames'] ) {
            $list = explode( ',', $this->strip_whitespace( $params['hostnames'] ) );
        }
        if( @$params['hostnamefile'] ) {
            $csv = implode( ',', file( @$params['hostnamefile'] ) );
            $list = explode( ',', $this->strip_whitespace( $csv ) );
        }
        foreach( $list as $idx => $host ) {
            if( !$host ) {
                unset( $list[$idx] );
                continue;
            }
        }
        if( !count( $list ) ) {
            throw new Exception( "No hostnames to process.", 2 );
        }
        $hostnames = $list;
        return $list;
    }

    /**
     * prints program version text
     */
    public function print_version() {
        $version = @file_get_contents(  __DIR__ . '/VERSION');
        echo $version ?: 'version unknown' . "\n";
    }    
    
    /**
     * prints CLI help text
     */
    public function print_help() {
         
        $buf = <<< END

   names2ips.php [options] --hostnames=<csv> | --hostnamefile=<file>

   This script generates a report of IP addresses given a list of hostnames.
   It can return IPs as integer,hex, or standard dot notation.
   It is possible to specify endian-ness of integer and hex values.

   Options:

    --hostnames=<csv>      comma separated list of bitcoin addresses
    --hostnamefile=<path>  file containing bitcoin addresses, one per line.
    --ipformat=<path>      longint|hex|dot    default=dot
    --groupby=<type>       hostname|none  default = hostname
    --sort=<type>          asc|desc|none  default = asc
    --format=<type>        json|csv|text|textcompact|code|printr
    --outfile=<file>       file to write report to instead of stdout.
    --endian=<type>        big|little.  used when ipformat is longint or hex.
                             default = little


END;

   fprintf( STDERR, $buf );       
        
    }

    /**
     * looks up hostnames
     */
    protected function process_hostnames() {
        
        $params = $this->get_params();
        $hostnames = $this->get_hostnames();
        
        $results = [];
        foreach( $hostnames as $hostname ) {
            if( $params['groupby'] == 'hostname' ) {
                $results[$hostname] = $this->sort_results( $this->lookup_host( $hostname ) );
            }
            else {
                $results = array_merge( $results, $this->lookup_host( $hostname ) );
            }
        }
        if( $params['groupby'] != 'hostname' ) {
            $results = $this->sort_results( $results );
        }
        
        return $results;
    }
    
    protected function sort_results( $results ) {
        $params = $this->get_params();
        switch( $params['sort'] ) {
            case 'asc':  sort($results); break;
            case 'desc': rsort($results); break;
        }
        return $results;
    }

    protected function lookup_host($hostname) {
        $addrs = gethostbynamel( $hostname );
        
        $addrs = is_array($addrs) ? $addrs : [];
        
        $endian = $this->is_little_endian() ? 'little' : 'big';
        $params = $this->get_params();
        if( in_array( $params['ipformat'], ['longint','hex'] )) {
            foreach( $addrs as &$addr ) {
                $addr = ip2long($addr);
                if( $params['endian'] != $endian ) {
                    $before = $addr;
                    $addr = $this->reverse_byte_order( $addr );
                }
                if( $params['ipformat'] == 'hex') {
                    $addr = sprintf( '0x%x', $addr );
                }
                else {
                    // for 32 bit systems, to avoid printing as neg number.
                    // see: http://php.net/manual/en/function.ip2long.php#refsect1-function.ip2long-notes
                    $addr = sprintf( '%u', $addr );
                }
            }
        }
        return $addrs;
    }
    
    /**
     * prints out single report in one of several possible formats,
     */
    protected function print_results( $results ) {
        $params = $this->get_params();
        $outfile = @$params['outfile'];
        $format = @$params['format'];
        
        report_writer::write_results( $results, $outfile, $format );
    }
    
    /**
     * removes whitespace from a string
     */
    protected function strip_whitespace( $str ) {
        return preg_replace('/\s+/', '', $str);
    }
    
    function reverse_byte_order($num) {
        $data = dechex($num);
        if (strlen($data) <= 2) {
            return $num;
        }
        $u = unpack("H*", strrev(pack("H*", $data)));
        $f = hexdec($u[1]);
        return $f;
    }
    
    function is_little_endian() {
        $testint = 0x00FF;
        $p = pack('S', $testint);
        return $testint===current(unpack('v', $p));
    }    
    
    
}


class report_writer {

    /**
     * prints out single report in specified format, either to stdout or file.
     */
    static public function write_results( $results, $outfile, $format ) {

        $fname = $outfile ?: 'php://stdout';
        $fh = fopen( $fname, 'w' );

        switch( $format ) {
            case 'text':  self::write_results_text( $fh, $results ); break;
            case 'textcompact':  self::write_results_textcompact( $fh, $results ); break;
            case 'code':  self::write_results_code( $fh, $results ); break;
            case 'printr':  self::write_results_printr( $fh, $results ); break;
            case 'csv':  self::write_results_csv( $fh, $results ); break;
            case 'json':  self::write_results_json( $fh, $results ); break;
            case 'html':  self::write_results_html( $fh, $results, $meta ); break;
            case 'jsonpretty':  self::write_results_jsonpretty( $fh, $results ); break;
        }

        fclose( $fh );

        if( $outfile ) {
            echo "\n\nReport was written to $fname\n\n";
        }
    }

    /**
     * writes out results in json (raw) format
     */
    static public function write_results_json( $fh, $results ) {
        fwrite( $fh, json_encode( $results ) );
    }

    /**
     * writes out results in jsonpretty format
     */
    static public function write_results_jsonpretty( $fh, $results ) {
        fwrite( $fh, json_encode( $results,  JSON_PRETTY_PRINT ) );
    }
    
    /**
     * writes out results in print_r format
     */
    static public function write_results_printr( $fh, $results ) {
        fwrite( $fh, print_r($results, true) );
    }
    
    /**
     * writes out results in csv format
     */
    static public function write_results_csv( $fh, $results, $hostname=null ) {
        
        if( self::results_are_grouped( $results )) {
            foreach( $results as $key => $group) {
                self::write_results_csv( $fh, $group, $key );
            }
        }
        else {
            if( $hostname ) {
                $results = array_merge( [$hostname], $results );
            }
            fputcsv( $fh, $results );
        }
    }

    /**
     * writes out results in text format
     */
    static public function write_results_text( $fh, $results ) {
        
        if( self::results_are_grouped( $results )) {
            foreach( $results as $key => $group) {
                fwrite($fh, "-- $key --\n" );
                self::write_results_text( $fh, $group );
                fwrite($fh, "\n");
            }
        }
        else {
            foreach( $results as $val ) {
                $val = str_pad( $val, strstr($val, '.') ? 17 : 12);
                fwrite( $fh, $val . "\n" );
            }
        }
    }

    
    /**
     * writes out results in textcompact format
     */
    static public function write_results_textcompact( $fh, $results ) {
        
        if( self::results_are_grouped( $results )) {
            foreach( $results as $key => $group) {
                fwrite($fh, "-- $key --\n" );
                self::write_results_textcompact( $fh, $group );
            }
        }
        else {
            $linebuf = '';
            foreach( $results as $val ) {
                $linebuf .= str_pad( $val, strstr($val, '.') ? 17 : 12);
                if( strlen( $linebuf ) > 80 ) {
                    $linebuf .= "\n";
                    fwrite( $fh, $linebuf );
                    $linebuf = '';
                }
            }
            if( $linebuf ) {
                fwrite( $fh, $linebuf . "\n" );
            }
        }
    }
    
    /**
     * writes out results in textcompact format
     */
    static public function write_results_code( $fh, $results ) {
        
        if( self::results_are_grouped( $results )) {
            foreach( $results as $key => $group) {
                fwrite($fh, "// -- $key --\n" );
                self::write_results_code( $fh, $group );
            }
        }
        else {
            $linebuf = '';
            foreach( $results as $val ) {
                $linebuf .= str_pad( $val . ",", strstr($val, '.') ? 17 : 12);
                if( strlen( $linebuf ) > 80 ) {
                    $linebuf .= "\n";
                    fwrite( $fh, $linebuf );
                    $linebuf = '';
                }
            }
            if( $linebuf ) {
                fwrite( $fh, $linebuf . "\n" );
            }
        }
    }

    static private function results_are_grouped( $results ) {
        
        $grouped = false;
        
        foreach( $results as $k => $v ) {
            $grouped = !is_integer( $k );
            break;
        }
        return $grouped;
    }   
}
