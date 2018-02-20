<?php

class Database{

    protected $server ="localhost";      // database server
    protected $user ="root";        // database login username
    protected $password ="";    // database login password
    protected $database ="oqra";    // database name

    protected $display_errors = false;

    public $affected_rows = 0;
    public $row_count = 0;
    public $last_result;
    public $insert_id = 0;
    public $prefix = '';


    private $connection = 0;

    /**
     * Construct database class
     * @param    $server     string
     * @param    $user       string
     * @param    $password   string
     * @param    $database   string
     */
    function __construct( $server, $user, $password, $database, $prefix = '' ){

        $this->server = $server;
        $this->user = $user;
        $this->password = $password;
        $this->database = $database;
        $this->prefix = $prefix;

        $this->connect();
    }

    /**
     * Connect to database and clear connection data
     */
    function connect(){

        if( !$this->connection ){
            $this->connection = @mysql_connect( $this->server, $this->user, $this->password );
        }

        if( !$this->connection ){
            echo "Connection to server failed.";
        }

        if( !@mysql_select_db( $this->database, $this->connection ) ){
            echo "Opening database failed.";
        }

        $this->server = "";
        $this->user = "";
        $this->password = "";
        $this->database = "";

    }

    /**
     * Check if connection is active
     * @return   bool
     */
    function have_connection(){
        $output = false;
        if( $this->connection ){
            $output = true;
        }
        return $output;
    }

    /**
     * Close connection
     */
    function disconect(){
        if( !@mysql_close( $this->connection) ){
            echo "Connection closing failed.";
        }
    }

    /**
     * Query database
     * @param    $query     string
     * @return   database resoult
     */
    function query( $query ){
        $output = false;

        // query database
        $result = @mysql_query( $query, $this->connection );
        //$result = @mysql_query( 'SELECT "value" FROM lv_options WHERE name="Description"', $this->connection );

        if( !$result ){
            // if query not successful
            if( $this->display_errors ){
                echo "<b>Query failed.</b><br />" . mysql_error( $this->connection );
            }
        }else{
            if( preg_match( '/^\s*(create|alter|truncate|drop) /i', $query ) ){
                // set output for this type of query
                $output = $result;
            }elseif( preg_match( '/^\s*(insert|delete|update|replace) /i', $query ) ){
                // save number of affected rows
                $this->affected_rows = mysql_affected_rows( $this->connection );
                if ( preg_match( '/^\s*(insert|replace) /i', $query ) ){
                    // save ID of last insert
                    $this->insert_id = mysql_insert_id( $this->connection );
                    $output = $this->insert_id;
                }else{
                    $output = $this->affected_rows;
                }
            }else{
                $this->last_result = array();
                $row_count = 0;
                while ( $row = @mysql_fetch_object( $result ) ) {
                    $this->last_result[$row_count] = $row;
                    $row_count++;
                }
                $this->row_count = $row_count;
                $output = $this->last_result;
            }
        }
        return $output;
    }

    function get_data( $query ){
        $output = false;
        $result = $this->query( $query );
        if( !empty( $result ) ){
            $values = array_values( get_object_vars( $result[0] ) );
            $output = ( isset( $values[0] ) && $values[0] !== '' ) ? $values[0] : null;
        }
        return $output;
    }

    function friendly( $string ){
        $new_string = htmlspecialchars( mysql_real_escape_string( $string ) );
        return $new_string;
    }

    function real_escape( $string ){
        return mysql_real_escape_string( $string );
    }

    /**
     * Query database
     * @param    $query     string
     * @return   database resoult
     */
    function insert( $table, $data ){
        if( count( $data ) > 0 ){
            $columns = array_keys( $data );
            $values = array_values( $data );
            $escaped_values = array();
            foreach( $data as $key => $value ){
                $escaped_values[] = $this->real_escape( $value );
            }
            $query = "INSERT INTO $table (" . implode( "," , $columns ) . ") VALUES ('" . implode( "','" , $escaped_values ) . "')";

            return $this->query( $query );
        }
    }

    function update( $table, $data, $where = array("id" => 0) ){
        if( count( $data ) > 0 ){
            $columns = array_keys( $data );
            $values = array_values( $data );
            $escaped_values = array();
            $escaped_where_values = array();
            foreach( $data as $key => $value ){
                $escaped_values[$key] = $this->real_escape( $value );
            }
            foreach( $where as $key => $value ){
                $escaped_where_values[$key] = $this->real_escape( $value );
            }

            $query_set = 'SET';
            foreach( $escaped_values as $key => $value ){
                $query_set .= ($query_set == 'SET') ? " $key = '$value'" : ", $key = '$value'" ;
            }
            $query_where = 'WHERE';
            foreach( $escaped_where_values as $key => $value ){
                $query_where .= ($query_where == 'WHERE') ? " $key = '$value'" : "AND $key = '$value'" ;
            }

            $query = "UPDATE $table " . $query_set . $query_where;

            return $this->query( $query );
        }
    }


    function delete( $table, $where = array("id" => 0) ){
        if( count( $where ) > 0 ){
            $values = array_values( $data );
            $escaped_where_values = array();
            foreach( $where as $key => $value ){
                $escaped_where_values[$key] = $this->real_escape( $value );
            }

            $query_where = 'WHERE';
            foreach( $escaped_where_values as $key => $value ){
                $query_where .= ($query_where == 'WHERE') ? " $key = '$value'" : "AND $key = '$value'" ;
            }

            $query = "DELETE FROM $table " . $query_where;

            return $this->query( $query );
        }
    }


}

?>