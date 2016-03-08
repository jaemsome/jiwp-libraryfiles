<?php

if(!defined('ABSPATH')) exit; // Exit if accessed directly

class KMI_LibraryFiles
{
    // Public variables
    public $general_settings = array();
    // Private variables
    private $__general_settings_key = 'kmi_library_files_general_settings';
    private $__plugin_options_key = 'kmi_library_files_menu_option';
    private $__library_files_dir;
    private $__library_files_arr = array();
    private $__plugin_settings_tabs = array();
    private $__message = array();
    
    public function __construct()
    {
        $this->__library_files_dir = plugin_dir_path(__FILE__).'library-files'.DIRECTORY_SEPARATOR;
        $this->__Setup_Shortcodes();
        $this->__Setup_Action_Hooks();
    }
    
    /*
     * Callback function for displaying the
     * library files download list.
     */
    public function LibraryFiles_Download_List()
    {
        ob_start();
        
        if(is_user_logged_in()):
        ?>
            <table id="kmi_library_files_list" class="kmi-table kmi-one-column">
                <thead>
                    <tr id="kmi_table_header">
                        <th class="bold bg-grey align-center">Filename</th>
                        <th class="bold bg-grey align-center kmi-four-columns">Size</th>
                        <th class="bold bg-grey align-center kmi-four-columns">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($this->__library_files_arr['libraries']) > 0): ?>
                        <?php foreach($this->__library_files_arr['libraries'] as $library_file): $lib_path_parts = pathinfo($library_file);  ?>
                            <tr id="library_<?php echo $lib_path_parts['filename']; ?>">
                                <td>
                                    <?php
                                        echo '<a href="?action=download&file='.$lib_path_parts['filename'].'" class="bold">';
                                        echo $lib_path_parts['basename'];
                                        echo '</a>';

                                        $summary_content = '';

                                        foreach($this->__library_files_arr['summaries'] as $summary_file)
                                        {
                                            if(strpos($summary_file, $lib_path_parts['filename']) !== FALSE)
                                            {
                                                $summary_content = file_get_contents($summary_file);

                                                if(!empty($summary_content))
                                                {
                                                    echo '<br/><br/><span class="bold italic">';
                                                    echo 'SUMMARY:</span><br/><span class="italic">';
                                                    echo nl2br($summary_content);
                                                    echo '</span>';
                                                }
                                                break;
                                            }
                                        }

                                        if(empty($summary_content))
                                            echo '<br/><br/><span class="italic">There\'s no summary text found for this library file.</span>';
                                    ?>
                                </td>
                                <td class="align-center"><?php echo $this->__Readable_File_Size(filesize($library_file)); ?></td>
                                <td class="align-center">
                                    <a href="?page=<?php echo $this->__plugin_options_key; ?>&action=download&file=<?php echo $lib_path_parts['filename']; ?>" class="dashicons dashicons-download btn-kmi-download-library-files" id="download_<?php echo $lib_path_parts['filename']; ?>" title="Download File" alt="Download File"></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr id="kmi_empty_row">
                            <td class="align-center" colspan="3">No library files found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php else: global $kmi_user_control; // KMI_UserControl object instance ?>
            <h2>You need to <a href="<?php echo $kmi_user_control->general_settings['kmi_user_control_login_url']; ?>">login</a> first. If you don't have an account yet you can register <a href="<?php echo $kmi_user_control->general_settings['kmi_user_control_register_url']; ?>">here</a>.</h2>
        <?php
        endif;
        
        return ob_get_clean();
    }
    
    public function Add_Admin_Option_Page()
    {
        if(empty($GLOBALS['admin_page_hooks']['kmi_menu_options']))
            add_menu_page('KMI Options', 'KMI Options', 'manage_options', 'kmi_menu_options', array($this, 'KMI_Options_Page'));
        
        if(empty($GLOBALS['admin_page_hooks'][$this->__plugin_options_key]))
        {
            $option_page = add_submenu_page('kmi_menu_options', 'KMI Library Files', 'Library Files', 'manage_options', $this->__plugin_options_key, array($this, 'Library_Files_Option_Page'));
            // Add css to the option page
            add_action('admin_print_styles-'.$option_page, array($this, 'Add_Option_Page_Styles'));
            // Add javascript to the option page
            add_action('admin_print_scripts-'.$option_page, array($this, 'Add_Option_Page_Scripts'));
        }
    }
    
    /*
     * KMI option page UI
     */
    public function KMI_Options_Page()
    {
        ?>
        <div class="wrap">
            <h2>Welcome to KMI Technology plugins. You can select the items under this menu to edit the desired plugin's settings.</h2>
        </div>
        <?php
    }
    
    public function Library_Files_Option_Page()
    {
        ?>
        <div class="wrap">
            <form id="kmi_libraryfiles_form" method="POST" action="" enctype="multipart/form-data">
                <?php if(!empty($this->__message['error'])): ?>
                    <p class="error kmi-message"><?php echo $this->__SetMessages($this->__message['error']); ?></p>
                <?php elseif(!empty($this->__message['success'])): ?>
                    <p class="success kmi-message"><?php echo $this->__SetMessages($this->__message['success']); ?></p>
                <?php endif; ?>
                <input type="hidden" name="kmi_codegenerator" value="true" />
                <?php
                    settings_fields($this->__general_settings_key);
                    
                    do_settings_sections($this->__general_settings_key);
                ?>
            </form>
        </div>
        <?php
    }
    
    /*
     * Setup all CSS and JS files for the site's front-end pages
     */
    public function Add_Front_End_Styles_And_Scripts()
    {
        if(!wp_style_is('kmi_global_style', 'registered'))
        {
            wp_register_style('kmi_global_style', plugins_url('css/global.css', __FILE__));
        }
        
        if(!wp_style_is('kmi_global_style', 'enqueued'))
        {
            wp_enqueue_style('kmi_global_style');
        }
        
        if(wp_style_is('dashicons', 'registered'))
        {
            wp_enqueue_style('dashicons');
        }
        
        if(!wp_script_is('kmi_global_script', 'registered'))
        {
            // Register the script that contains the kmi global functions
            wp_register_script('kmi_global_script', plugins_url('js/kmi-global.js', __FILE__), array('jquery'), false, true);
        }
        
        if(!wp_script_is('kmi_libraryfiles_script', 'registered'))
        {
            // Register the script for the plugin
            wp_register_script('kmi_libraryfiles_script', plugins_url('js/kmi-libraryfiles.js', __FILE__), array('kmi_global_script'), false, true);
        }
        
        if(!wp_script_is('kmi_libraryfiles_script', 'enqueued'))
        {
            // Enqueue the plugin script
            wp_enqueue_script('kmi_libraryfiles_script');
        }
    }
    
    /*
     * Adding css for the option page
     */
    public function Add_Option_Page_Styles()
    {
        if(!wp_style_is('kmi_global_style', 'registered'))
        {
            wp_register_style('kmi_global_style', plugins_url('css/global.css', __FILE__));
        }
        
        if(!wp_style_is('kmi_global_style', 'enqueued'))
        {
            wp_enqueue_style('kmi_global_style');
        }
    }
    
    /*
     * Adding javascripts for the option page
     */
    public function Add_Option_Page_Scripts()
    {
        if(!wp_script_is('kmi_global_script', 'registered'))
        {
            // Register the script that contains the kmi global functions
            wp_register_script('kmi_global_script', plugins_url('js/kmi-global.js', __FILE__), array('jquery'), false, true);
        }
        
        if(!wp_script_is('kmi_libraryfiles_script', 'registered'))
        {
            // Register the script for the plugin
            wp_register_script('kmi_libraryfiles_script', plugins_url('js/kmi-libraryfiles-admin.js', __FILE__), array('kmi_global_script'), false, true);
        }
        
        if(!wp_script_is('kmi_libraryfiles_script', 'enqueued'))
        {
            // Enqueue the plugin script
            wp_enqueue_script('kmi_libraryfiles_script');
        }
        
        // Ajax object variables
        $ajax_obj_variables_arr = array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'option_page' => $this->__plugin_options_key,
            'ajax_loader' => get_site_url().'/wp-admin/images/loading.gif'
        );
        wp_localize_script('kmi_libraryfiles_script', 'ajax_object', $ajax_obj_variables_arr);
    }
    
    public function Initialization()
    {
        // For the tab control
        $this->general_settings = (array)get_option($this->__general_settings_key);
        
        // Merge with defaults
	$this->general_settings = array_merge(
            array(
                'general_option' => 'General value'
            ),
            $this->general_settings
        );
        
        // Retrieve all library archive and summary text files
        $this->__library_files_arr['libraries'] = glob($this->__library_files_dir.'*.zip');
        $this->__library_files_arr['summaries'] = glob($this->__library_files_dir.'*.txt');
        
        // Download process
        if($_GET['action'] === 'download' && !empty($_GET['file']))
        {
            if(strpos($this->__GetPostContent(), '[kmi_libraryfiles_download_list]') !== FALSE)
                $this->__ActionDownload($_GET['file']);
        }
    }
    
    public function Register_Admin_Option_Settings()
    {
        // Register general settings
        $this->__plugin_settings_tabs[$this->__general_settings_key] = 'General';
        register_setting($this->__general_settings_key, $this->__general_settings_key);
        // Add section on general settings
        add_settings_section('kmi_library_files_list_section', 'KMI Library Files', array($this, 'Library_Files_List_Section'), $this->__general_settings_key);
        
        // File upload process
        if(isset($_POST['kmi_upload_library_files']))
        {
            $this->__ActionUploadFile($_FILES['kmi_libraryfile']);
        }
        
        // Download process
        if($_GET['page'] === $this->__plugin_options_key && $_GET['action'] === 'download' && !empty($_GET['file']))
        {
            $this->__ActionDownload($_GET['file']);
        }
        // Delete process
        else if($_GET['page'] === $this->__plugin_options_key && $_GET['action'] === 'delete' && !empty($_GET['file']))
        {
            $this->__ActionDelete($_GET['file']);
        }
        
        // Retrieve all library archive and summary text files
        $this->__library_files_arr['libraries'] = glob($this->__library_files_dir.'*.zip');
        $this->__library_files_arr['summaries'] = glob($this->__library_files_dir.'*.txt');
    }
    
    public function Library_Files_List_Section()
    {
        ?>
        <p>
            <label class="bold" for="kmi_libraryfile">Allowed files are ZIP and TEXT only. Please select a file not larger than 2MB.</label><br/>
            <input type="file" name="kmi_libraryfile" id="kmi_libraryfile" /><br/>
            <input type="submit" id="btn_kmi_upload_library_files" class="button button-primary" value="Upload Library Files" name="kmi_upload_library_files" />
        </p>
        <!--<p class="submit"><input type="submit" name="submit" value="Upload File"></input></p>-->
        <table id="kmi_library_files_list" class="kmi-table kmi-one-column">
            <thead>
                <tr id="kmi_table_header">
                    <th class="bg-grey">Filename</th>
                    <th class="bg-grey kmi-four-columns">Size</th>
                    <th class="bg-grey kmi-four-columns">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($this->__library_files_arr['libraries']) > 0): ?>
                    <?php foreach($this->__library_files_arr['libraries'] as $library_file): $lib_path_parts = pathinfo($library_file);  ?>
                        <tr id="library_<?php echo $lib_path_parts['filename']; ?>">
                            <td>
                                <?php
                                    echo '<a href="?page='.$this->__plugin_options_key.'&action=download&file='.$lib_path_parts['filename'].'" class="bold">';
                                    echo $lib_path_parts['basename'];
                                    echo '</a>';
                                    
                                    $summary_content = '';
                                    
                                    foreach($this->__library_files_arr['summaries'] as $summary_file)
                                    {
                                        if(strpos($summary_file, $lib_path_parts['filename']) !== FALSE)
                                        {
                                            $summary_content = file_get_contents($summary_file);
                                            
                                            if(!empty($summary_content))
                                            {
                                                echo '<br/><br/><span class="bold italic">';
                                                echo 'SUMMARY:</span><br/><span id="summary_'.$lib_path_parts['filename'].'" class="italic">';
                                                echo nl2br($summary_content);
                                                echo '</span>';
                                            }
                                            break;
                                        }
                                    }
                                    
                                    if(empty($summary_content))
                                        echo '<br/><br/><span id="summary_'.$lib_path_parts['filename'].'" class="italic">There\'s no summary text found for this library file.</span>';
                                ?>
                            </td>
                            <td class="align-center"><?php echo $this->__Readable_File_Size(filesize($library_file)); ?></td>
                            <td class="align-center">
                                <a href="?page=<?php echo $this->__plugin_options_key; ?>&action=download&file=<?php echo $lib_path_parts['filename']; ?>" class="dashicons dashicons-download btn-kmi-download-libraryfiles" id="download_<?php echo $lib_path_parts['filename']; ?>" title="Download File" alt="Download File"></a>
                                <!--<a href="?page=<?php echo $this->__plugin_options_key; ?>&action=edit&file=<?php echo $lib_path_parts['filename']; ?>" class="dashicons dashicons-edit btn-kmi-edit-library-files" id="edit_<?php echo $lib_path_parts['filename']; ?>" title="Edit File" alt="Edit File"></a>-->
                                <a href="?page=<?php echo $this->__plugin_options_key; ?>&action=delete&file=<?php echo $lib_path_parts['filename']; ?>" class="dashicons dashicons-trash btn-kmi-delete-libraryfiles" id="delete_<?php echo $lib_path_parts['filename']; ?>" title="Delete File"></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr id="kmi_empty_row">
                        <td class="align-center" colspan="3">No library files found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <?php
    }
    
    /*
     * Ajax upload function
     */
    public function Ajax_Action_UploadFile()
    {
        $response_arr = array();
        
        $this->__ActionUploadFile($_FILES['kmi_libraryfile']);
        
        if(!empty($this->__message['error']))
        {
            $response_arr['error'] = $this->__SetMessages($this->__message['error']);
            unset($this->__message['error']);
        }
        else if(!empty ($this->__message['success']))
        {
            $response_arr['success'] = $this->__SetMessages($this->__message['success']);
            unset($this->__message['success']);
            
            // Add the file basic info to the response
            if(!empty($this->__message['file']))
                $response_arr['file'] = $this->__message['file'];
        }
        
//        $response_arr['error'] = 'Sorry, the ajax upload function is under construction. Please try again later.......';
        
        echo json_encode($response_arr);
        wp_die();
    }
    
    /*
     * Ajax delete function
     */
    public function Ajax_Action_Delete()
    {
        $response_arr = array();
        
        list($action, $file_name) = explode('_', $_POST['cmd_info']);
        
        if($action === 'delete')
        {
            $this->__ActionDelete($file_name);

            if(!empty($this->__message['error']))
            {
                $response_arr['error'] = $this->__SetMessages($this->__message['error']);
                unset($this->__message['error']);
            }
            else if(!empty ($this->__message['success']))
            {
                $response_arr['success'] = $this->__SetMessages($this->__message['success']);
                unset($this->__message['success']);
                
                // Add the file basic info to the response
                if(!empty($this->__message['file']))
                    $response_arr['file'] = $this->__message['file'];
            }
        }
        else
            $response_arr['error'] = 'Sorry, unrecognized command.';
        
        echo json_encode($response_arr);
        wp_die();
    }
    
    /*
     * Upload file from local machine into the server
     */
    private function __ActionUploadFile($files)
    {
        $max_filesize = 2097152; // 2MB
        
        // check if we can upload to the specified path, if not DIE and inform the user
        if(!is_writable($this->__library_files_dir))
        {
            $this->__message['error']['upload'][] = 'Can\'t upload to the destination folder, please CHMOD it to 777.';
        }
        else
        {
            $file_basename = sanitize_text_field($files['name']);
            
            $target_file = $this->__library_files_dir.basename($file_basename);
            // Get the uploaded file type
            $target_file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
            
            // Check if file already exists
            if(file_exists($target_file))
            {
                $this->__message['error']['upload'][] = 'Sorry, '.$file_basename.' already exists.';
            }
            
            // Check file size
            if($files['size'] > $max_filesize)
            {
                $this->__message['error']['upload'][] = 'Sorry, '.$file_basename.' is too large.';
            }
            
            // Allow certain file formats
            if($target_file_type !== 'txt' && $target_file_type !== 'zip')
            {
                $this->__message['error']['upload'][] = 'Sorry, only ZIP and TXT files are allowed.';
            }
            
            if(empty($this->__message['error']['upload']))
            {
                if(move_uploaded_file($files['tmp_name'], $target_file))
                {
                    $this->__message['success'] = 'The file '.$file_basename.' has been uploaded.';
                    
                    $file_name = basename($file_basename, '.'.$target_file_type);
                    
                    if($target_file_type === 'zip')
                    {
                        $this->__message['file']['library']['name'] = $file_name;
                        $this->__message['file']['library']['size'] = $this->__Readable_File_Size(filesize($target_file));
                    }
                    else if($target_file_type === 'txt')
                    {
                        $this->__message['file']['summary']['name'] = $file_name;
                        $this->__message['file']['summary']['content'] = nl2br(file_get_contents($target_file));
                    }
                }
                else
                {
                    $this->__message['error']['upload'][] = 'Sorry, there was an error uploading your file. Please try again later.';
                }
            }
        }
    }
    
    /*
     * Execute the donwload process
     */
    private function __ActionDownload($file_name='')
    {
        $file_name = sanitize_text_field($file_name);
        
        $library_file = $this->__library_files_dir.$file_name.'.zip';
        $summary_file = $this->__library_files_dir.$file_name.'.txt';
        
        // Download the file
        if(in_array($library_file, $this->__library_files_arr['libraries']))
        {
            $zip_name = $file_name.'.zip';
            $zip = new ZipArchive();
            $zip->open($zip_name, 1?ZIPARCHIVE::OVERWRITE:ZIPARCHIVE::CREATE===TRUE);
            
            // Add the library file
            $zip->addFile($library_file, basename($library_file));
            // Add the summary text
            if(in_array($summary_file, $this->__library_files_arr['summaries']))
                $zip->addFile($summary_file, basename($summary_file));
            
            $zip->close();
            
            header('Content-Type: application/zip');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="'.basename($zip_name).'"');
            header('Content-Transfer-Encoding: binary');
            header('Content-length: '.filesize($zip_name));
            ob_end_clean();
            readfile($zip_name);
            exit();
        }
    }
    
    /*
     * Execute the delete process
     */
    private function __ActionDelete($file_name='')
    {
        $file_name = sanitize_text_field($file_name);
        
        $library_file = $this->__library_files_dir.$file_name.'.zip';
        $summary_file = $this->__library_files_dir.$file_name.'.txt';
        
        // Remove the ZIP file
        if(in_array($library_file, $this->__library_files_arr['libraries']))
        {
            if(unlink($library_file))
            {
                $this->__library_files_arr['libraries'] = array_diff($this->__library_files_arr['libraries'], array($library_file));
                $this->__message['success'][] = basename($library_file).' has been deleted.';
                // Save file basic info
                $this->__message['file']['name'] = basename($library_file, '.zip');
            }
            else
                $this->__message['error'][] = 'Sorry, there was an error deleting '.basename($library_file).' file. Please try again later.';
        }
        
        // Remove the TEXT file
        if(in_array($summary_file, $this->__library_files_arr['summaries']))
        {
            if(unlink($summary_file))
            {
                $this->__library_files_arr['summaries'] = array_diff($this->__library_files_arr['summaries'], array($summary_file));
                $this->__message['success'][] = basename($summary_file).' has been deleted.';
            }
            else
                $this->__message['error'][] = 'Sorry, there was an error deleting '.basename($summary_file).' file. Please try again later.';
        }
    }
    
    private function __SetMessages($messages='', $source='')
    {
        $msgs = '';
        
        if(is_array($messages))
        {
            foreach($messages as $src => $msg)
            {
                $new_src = is_numeric($src) ? $source : $src;
                $msgs .= $this->__SetMessages($msg, $new_src);
            }
        }
        else
        {
            if(!empty($source))
                $msgs .= '<span class="bold">'.ucwords(str_replace('_', ' ', $source)).'</span>: ';
            $msgs .= $messages.'<br/>';
        }
        
        return $msgs;
    }

    /*
     * Converts bytes to a human readable filesize
     */
    private function __Readable_File_Size($bytes, $decimals=2)
    {
        $size_arr = array('B','KB','MB','GB','TB','PB','EB','ZB','YB');
        
        $factor = floor((strlen($bytes) - 1) / 3);
        
        return sprintf("%.{$decimals}f ", $bytes / pow(1024, $factor)) . $size_arr[$factor];
    }
    
    private function __GetPostContent()
    {
        $url = explode('?', 'http://'.$_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
        
        $post_ID = url_to_postid($url[0]);
        
        return get_post_field('post_content', $post_ID);
    }
    
    /*
     * Add shortcode hooks
     */
    private function __Setup_Shortcodes()
    {
        // Library files download list
        add_shortcode('kmi_libraryfiles_download_list', array($this, 'LibraryFiles_Download_List'));
    }
    
    /*
     * Add action hooks
     */
    private function __Setup_Action_Hooks()
    {
        // Add initialization functions when loading the site
        add_action('init', array($this, 'Initialization'));
        // Add front-end css and scripts
        add_action('wp_enqueue_scripts', array($this, 'Add_Front_End_Styles_And_Scripts'));
        // Add option page in the admin panel
        add_action('admin_menu', array($this, 'Add_Admin_Option_Page'));
        // Register the settings to use on the admin option pages
        add_action('admin_init', array($this, 'Register_Admin_Option_Settings'));
        // Ajax functions
        add_action('wp_ajax_upload_kmilibraryfile', array($this, 'Ajax_Action_UploadFile'));
        add_action('wp_ajax_delete_kmilibraryfile', array($this, 'Ajax_Action_Delete'));
    }
}

$kmi_libaryfiles = new KMI_LibraryFiles();