jQuery(document).ready(function($){
    var $kmi_libraryfiles_tablelist = $('table#kmi_library_files_list');
    
    $.fn.extend({
        KMI_Upload_LibraryFiles: function() {
            var form = this;
            
            form.find('input[type="file"]').on('change', function(){
                var file = this.files[0];
                var file_type = file.type.toLowerCase();
                var accepted_file_types = ['application/zip','text/plain'];
                if(!((file_type === accepted_file_types[0]) || (file_type === accepted_file_types[1])))
                {
                    // Delete previous message
                    $('p.kmi-message').remove();
                    // Add error message
                    form.prepend('<p class="error kmi-message">Please select a valid file.</p>');
                    // Reset the form
                    form.trigger('reset');
                }
            });
            
            form.on('submit', function(e) {
                e.preventDefault();
                
                var form_data = new FormData(this);
                // Append the action property
                form_data.append('action', 'upload_kmilibraryfile');
                
                $.ajax({
                    url: ajax_object.ajax_url,
                    type: 'POST',
                    dataType: 'json',
                    data: form_data,
                    contentType: false,     // The content type used when sending data to the server.
                    cache: false,           // To unable request pages to be cached
                    processData: false,     // To send DOMDocument or non processed data file it is set to false
                    success: function(response) {
                        // Delete previous message
                        $('p.kmi-message').remove();

                        if(response.error) {
                            // Add error message
                            form.prepend('<p class="error kmi-message">'+response.error+'</p>');
                        } else if(response.success) {
                            // Add success message
                            form.prepend('<p class="success kmi-message">'+response.success+'</p>');
                            
                            // Add the new uploaded library into the list
                            if(response.file.library)
                            {
                                var $table_list = $('table#kmi_library_files_list tbody');
                                var new_library_item = '<tr id="library_'+response.file.library.name+'">';
                                new_library_item += '<td>';
                                new_library_item += '<a href="?page=kmi_library_files_menu_option&action=download&file='+response.file.library.name+'" class="bold">';
                                new_library_item += response.file.library.name+'.zip';
                                new_library_item += '</a>';
                                new_library_item += '<br/><br/><span id="summary_'+response.file.library.name+'" class="italic">There\'s no summary text found for this library file.</span>';
                                new_library_item += '</td>';
                                new_library_item += '<td class="align-center">'+response.file.library.size+'</td>';
                                new_library_item += '<td class="align-center">';
                                new_library_item += '<a href="?page=kmi_library_files_menu_option&action=download&file='+response.file.library.name+'" class="dashicons dashicons-download btn-kmi-download-libraryfiles-new" id="download_'+response.file.library.name+'" title="Download File" alt="Download File"></a>&nbsp;';
                                new_library_item += '<a href="?page=kmi_library_files_menu_option&action=delete&file='+response.file.library.name+'" class="dashicons dashicons-trash btn-kmi-delete-libraryfiles-new" id="delete_'+response.file.library.name+'" title="Delete File"></a>';
                                new_library_item += '</td>';
                                new_library_item += '</tr>';
                                $table_list.append(new_library_item);
                                
                                // Apply the list actions to the newly added item
                                $kmi_libraryfiles_tablelist.KMI_TableList_Pagination();
                                $('.btn-kmi-delete-libraryfiles-new').KMI_Delete_LibraryFiles();
                            }
                            // Add the new uploaded summary to the list item
                            else if(response.file.summary)
                            {
                                var $table_item_summary = $('table#kmi_library_files_list tbody tr#library_'+response.file.summary.name+' td span#summary_'+response.file.summary.name);
                                var summary_title = '<span class="bold italic">SUMMARY:</span><br/>';
                                // Insert title first
                                $table_item_summary.before(summary_title);
                                // Insert summary content
                                $table_item_summary.html(response.file.summary.content);
                            }
                            // Reset the form
                            form.trigger('reset');
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.log('ERRORS: '+ textStatus);
                    }
                });
            });
        },
        KMI_Delete_LibraryFiles: function() {
            var data = {action: 'delete_kmilibraryfile'};
            
            // Set data info into the POST data
            $(this).click(function(){
                data.cmd_info = $(this).attr('id');
            });
            
            function Success_Callback_Function(response) {
                // Action successfully executed
                if(response.success && response.file) {
                    // Remove the deleted row
                    $('table#kmi_library_files_list tbody tr#library_'+response.file.name).remove();
                    
                    // Add empty row if no more items on the list
                    var row_count = $('table#kmi_library_files_list tbody tr:last').index();
                    if(row_count < 1) {
                        $('table#kmi_library_files_list tbody').append('<tr id="kmi_empty_row"><td class="align-center" colspan="3">No library files found.</td></tr>');
                    }
                }
            }
            
            KMI_AjaxClick($(this), data, $('form#kmi_libraryfiles_form'), null, null, Success_Callback_Function);
        }
    });
    
    $('form#kmi_libraryfiles_form').KMI_Upload_LibraryFiles();
    $('.btn-kmi-delete-libraryfiles').KMI_Delete_LibraryFiles();
    $kmi_libraryfiles_tablelist.KMI_TableList_Pagination();
});