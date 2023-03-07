<?php 

/**
 * Dashboard Widget
 *
 * @package       DashboardWidget
 * @author        Eva Tucker 
 * @version       1.0.1
 *
 * @wordpress-plugin
 * Plugin Name:   Dashboard Widget
 * Plugin URI:    
 * Description:   
 * Version:       1.0.1
 * Author:        Eva Tucker
 * Author URI:    
 * Text Domain:   dashboard-widget
 * Domain Path:   /languages 
 * License:       GPLv2
 * License URI:   https://www.gnu.org/licenses/gpl-2.0.html
 *  
*/


/* Exit if accessed directly. */
if ( ! defined( 'ABSPATH' ) ) exit;

define( 'DBGW_PLUGIN', __FILE__ );
define( 'DBGW_PLUGIN_TABLE', 'graph_widget_data' );
define( 'DBGW_PLUGIN_BASENAME', plugin_basename( DBGW_PLUGIN ));
define( 'DBGW_PLUGIN_URL', plugins_url('', __FILE__));
define( 'DBGW_PLUGIN_BASE_PATH', plugin_dir_path(__FILE__));
register_deactivation_hook( __FILE__, ['dashboardGraphWidget', 'graph_widget_plugin_deactivate'] );
register_activation_hook( __FILE__, ['dashboardGraphWidget', 'graph_widget_plugin_activate'] );


if(!class_exists('dashboardGraphWidget')){
    
    class dashboardGraphWidget{
        
        /* The code for Load text-domain */
        public function __construct() {
            add_action('plugins_loaded', [$this, 'dashboard_graph_widget_load_textdomain']);
            add_action( 'init', [$this, 'dashboard_widget_init'] );
        }

        public function load() {
            add_action( 'rest_api_init',  [$this, 'add_end_point_graph_dashboard_widget']);
            add_action('wp_dashboard_setup', [$this, 'add_graph_dashboard_widget']);
            add_action( 'admin_enqueue_scripts', [$this, 'dashboard_widget_enqueue_admin_script'] );
        }


        /* The code for active plugin */
        public static function graph_widget_plugin_activate(){
                        
            /* Create table when active plugin */    
            global $wpdb;
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            $table_name = $wpdb->prefix . DBGW_PLUGIN_TABLE;
            if($wpdb->get_var("SHOW TABLES LIKE '".$table_name."'") != $table_name) {
                $sql = "CREATE TABLE IF NOT EXISTS `{$table_name}` (
                            `id` INT(11) NOT NULL AUTO_INCREMENT,
                            `name` VARCHAR(200) NOT NULL ,
                            `line` INT(11) NOT NULL ,
                            `line_two` INT(11) NOT NULL ,
                            `create_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                            PRIMARY KEY (`id`)) ENGINE = InnoDB;";
                dbDelta($sql);  

                // Insert inital data
                $myfile = fopen(DBGW_PLUGIN_BASE_PATH."/static-data.json", "r");
                $json = fread($myfile,filesize(DBGW_PLUGIN_BASE_PATH."/static-data.json"));
                fclose($myfile);
                $format = array( '%s', '%d', '%d', '%s');
                $static_data = json_decode($json, true);
                foreach($static_data as $single_row){
                    $data = array('name' => strtoupper($single_row['name']), 'line' => $single_row['line'], 'line_two' => $single_row['line_two'], 'create_at' => date('Y-m-d h:i:S', $single_row['create_at']));
                    $wpdb->insert($table_name,$data,$format);
                }
            }
        } 

        /* The code for deactive plugin */
        public static function graph_widget_plugin_deactivate(){
            /* Drop table when plugin deactivate */    
            global $wpdb;
            $table_name = $wpdb->prefix . DBGW_PLUGIN_TABLE;
            $sql = "DROP TABLE IF EXISTS $table_name";
            $wpdb->query($sql);
        } 

        public function add_end_point_graph_dashboard_widget(){
            register_rest_route( '/dashboard-widget/v1', '/getdatafor/(?P<for>[a-zA-Z0-9-_]++)', array(
                'methods' => 'GET',
                'callback' => [$this, 'dashboard_graph_information'],
                'permission_callback' => '__return_true',
            ) );
        }


        public function dashboard_graph_information(WP_REST_Request $request){
            $chart_data_for = $request['for'];
            $return_data = array();
            $get_data_for = 7;
            
            if($chart_data_for == 'last_15_days'){
                $get_data_for = 15;
            }
            if($chart_data_for == 'last_1_month'){
                $get_data_for = 30;
            }
            global $wpdb;
            $table_name = $wpdb->prefix . DBGW_PLUGIN_TABLE;
            $result = $wpdb->get_results("SELECT `name`, `line`, `line_two` FROM `{$table_name}` LIMIT ".$get_data_for, ARRAY_A);
            $return_data = array();
            if(count($result) > 0){
                $return_data = $result;
            }
            

            wp_send_json( array( 'success' => true, 'data' => $return_data ),200);
        }

        /* The code for Load text-domain */
        public function dashboard_graph_widget_load_textdomain() {
            $result = load_plugin_textdomain( 'dashboard-widget', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' ); 
        }   

        /* To add dashboard metabox at dashboard */
        public function add_graph_dashboard_widget() {
            global $wp_meta_boxes;
            wp_add_dashboard_widget('dashboard_graph_widget', 'Graph Widget', [$this, 'dashboard_graph_widget']);
        }

        // HTML content for the widget body
        public function dashboard_graph_widget() { 
            ?>
            <div class="textright">
                <select id="filter" onchange="filter_data(this.value);">
                    <option value="<?php echo __('last_7_days', 'dashboard-widget'); ?>"><?php echo __('Last 7 Days', 'dashboard-widget'); ?></option>
                    <option value="<?php echo __('last_15_days', 'dashboard-widget'); ?>"><?php echo __('Last 15 Days', 'dashboard-widget'); ?></option>
                    <option value="<?php echo __( 'last_1_month', 'dashboard-widget'); ?>"><?php echo __('Last Month', 'dashboard-widget'); ?></option>
                </select>
            </div>
            <div id="dashboard_chart"></div>
        <?php
        }

        // Register widget js and CSS
        public function dashboard_widget_init() {
            wp_register_script("dashboard_widget_react_production", "https://unpkg.com/react/umd/react.production.min.js" , array(), "1.0", false);
            wp_register_script("dashboard_widget_react_dom_production", "https://unpkg.com/react-dom/umd/react-dom.production.min.js" , array(), "1.0", false);
            wp_register_script("dashboard_widget_react_prototype", "https://unpkg.com/prop-types/prop-types.min.js" , array(), "1.0", false);
            wp_register_script("dashboard_widget_rechart", DBGW_PLUGIN_URL."/js/Recharts.js" , array(), "1.0", false);
            wp_register_script("dashboard_widget_main_js",DBGW_PLUGIN_URL."/js/main.js" , array(), "1.0", false);
        }

        // Enqueue script if page is admin dashboard
        public function dashboard_widget_enqueue_admin_script( $hook ) {
            if ( 'index.php' != $hook ) {
                return;
            }
            wp_enqueue_script( 'dashboard_widget_react_production');
            wp_enqueue_script( 'dashboard_widget_react_dom_production');
            wp_enqueue_script( 'dashboard_widget_react_prototype');
            wp_enqueue_script( 'dashboard_widget_rechart');
            wp_enqueue_script( 'dashboard_widget_main_js');
            wp_localize_script( 'dashboard_widget_main_js', 'graph_api_end_point',
                array( 
                    'resturl' => get_rest_url( DBGW_PLUGIN_BASENAME,'dashboard-widget/v1/getdatafor' )
                )
            );
        }
    }
}

global $dbgw;
$dbgw = new dashboardGraphWidget();
$dbgw->load();
