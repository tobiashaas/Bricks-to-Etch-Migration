
window.wpvividmu = window.wpvividmu || {};

(function($, w, undefined)
{
    w.wpvividmu.media={
        progress_queue:[],
        lock:false,
        init:function()
        {
            $( document ).on( 'click', '.wpvivid-mu-media-item a.wpvivid-mu-media', this.optimize_image );
            $( document ).on( 'click', '.wpvivid-mu-media-item a.wpvivid-mu-media-restore', this.restore_image);
            $( document ).on( 'click', '.misc-pub-wpvivid a.wpvivid-mu-media-restore', this.restore_image_edit);
            $( document ).on( 'click', '.misc-pub-wpvivid a.wpvivid-mu-media', this.optimize_image_edit);
            $( document ).on( 'click', '.wpvivid-media-attachment a.wpvivid-mu-media', this.optimize_image_attachment);
            $( document ).on( 'click', '.wpvivid-media-attachment a.wpvivid-mu-media-restore', this.restore_image_attachment);
            $( document ).on( 'click', '.thumbnail', this.get_attachment_progress);
            w.wpvividmu.media.get_progress();
        },
        optimize_image:function ()
        {
            if(w.wpvividmu.media.islockbtn())
            {
                return ;
            }
            var id=$( this ).data( 'id' );
            var site_id=$( this ).data( 'site' );
            $( this ).html("Optimizing...");
            $( this ).removeClass('wpvivid-mu-media');
            w.wpvividmu.media.lockbtn(true);

            var ajax_data = {
                'action': 'wpvivid_mu_opt_single_image',
                'id':id,
                'site_id':site_id
            };
            wpvivid_post_request(ajax_data, function(data)
            {
                w.wpvividmu.media.get_progress();

            }, function(XMLHttpRequest, textStatus, errorThrown)
            {
                w.wpvividmu.media.get_progress();
            });
        },
        optimize_image_edit:function()
        {
            if(w.wpvividmu.media.islockbtn())
            {
                return ;
            }
            var id=$( this ).data( 'id' );
            var site_id=$( this ).data( 'site' );
            $( this ).html("Optimizing...");
            $( this ).removeClass('wpvivid-mu-media');
            w.wpvividmu.media.lockbtn(true);

            var ajax_data = {
                'action': 'wpvivid_mu_opt_single_image',
                'id':id,
                'site_id':site_id
            };
            wpvivid_post_request(ajax_data, function(data)
            {
                w.wpvividmu.media.get_progress('edit');

            }, function(XMLHttpRequest, textStatus, errorThrown)
            {
                w.wpvividmu.media.get_progress('edit');
            });
        },
        optimize_image_attachment:function()
        {
            if(w.wpvividmu.media.islockbtn())
            {
                return ;
            }
            var id=$( this ).data( 'id' );
            var site_id=$( this ).data( 'site' );
            $( this ).html("Optimizing...");
            $( this ).removeClass('wpvivid-mu-media');
            w.wpvividmu.media.lockbtn(true);

            var ajax_data = {
                'action': 'wpvivid_mu_opt_single_image',
                'id':id,
                'site_id':site_id
            };

            wpvivid_post_request(ajax_data, function(data)
            {
                w.wpvividmu.media.get_progress('attachment');

            }, function(XMLHttpRequest, textStatus, errorThrown)
            {
                w.wpvividmu.media.get_progress('attachment');
            });
        },
        optimize_timeout_image:function (page='media')
        {
            var ajax_data = {
                'action': 'wpvivid_opt_image_ex',
            };
            wpvivid_post_request(ajax_data, function(data)
            {
                setTimeout(function ()
                {
                    w.wpvividmu.media.get_progress(page);
                }, 1000);

            }, function(XMLHttpRequest, textStatus, errorThrown)
            {
                setTimeout(function ()
                {
                    w.wpvividmu.media.get_progress(page);
                }, 1000);
            });
        },
        get_progress:function(page='media')
        {
            var ids=[];
            if(page=='media')
            {
                var media=$('.wpvivid-mu-media-item');
                if ( media.length>0 )
                {
                    media.each( function()
                    {
                        ids.push( $( this ).data( 'id' ) );
                    } );
                }
            }
            else if(page=='attachment')
            {
                var id=$('.wpvivid-media-attachment').data( 'id' );
                ids.push(id );
            }
            else
            {
                var id=$('.misc-pub-wpvivid').data( 'id' );
                ids.push(id );
            }

            if(ids.length<1)
            {
                return;
            }
            var ids_json=JSON.stringify(ids);
            var ajax_data = {
                'action': 'wpvivid_get_mu_opt_single_image_progress',
                ids:ids_json,
                page:page
            };

            wpvivid_post_request(ajax_data, function(data)
            {
                try
                {
                    var jsonarray = jQuery.parseJSON(data);
                    w.wpvividmu.media.update(jsonarray,page);
                    if (jsonarray.result === 'success')
                    {
                        if(jsonarray.continue)
                        {
                            setTimeout(function ()
                            {
                                w.wpvividmu.media.get_progress(page);
                            }, 1000);
                        }
                        else if(jsonarray.finished)
                        {
                            w.wpvividmu.media.lockbtn(false);
                        }
                        else
                        {
                            w.wpvividmu.media.optimize_timeout_image(page);
                        }

                    }
                    else
                    {
                        if(jsonarray.timeout)
                        {
                            w.wpvividmu.media.optimize_timeout_image(page);
                        }
                        else
                        {
                            w.wpvividmu.media.lockbtn(false);
                        }
                    }
                }
                catch(err)
                {
                    alert(err);
                    w.wpvividmu.media.lockbtn(false);
                }

            }, function(XMLHttpRequest, textStatus, errorThrown)
            {
                w.wpvividmu.media.get_progress(page);
            });
        },
        update:function (jsonarray,page='media')
        {
            if(page=='edit')
            {
                var id=$('.misc-pub-wpvivid').data( 'id' );
                if(jsonarray.hasOwnProperty(id))
                {
                    $( '.misc-pub-wpvivid' ).html(jsonarray[id]['html']);
                }
            }
            else if(page=='attachment')
            {
                var media=$('.wpvivid-media-attachment');
                if ( media.length>0 )
                {
                    media.each( function()
                    {
                        var id=$( this ).data( 'id' );
                        if(jsonarray.hasOwnProperty(id))
                        {
                            $( this ).html(jsonarray[id]['html']);
                        }
                    } );
                }
            }
            else
            {
                var media=$('.wpvivid-mu-media-item');
                if ( media.length>0 )
                {
                    media.each( function()
                    {
                        var id=$( this ).data( 'id' );
                        if(jsonarray.hasOwnProperty(id))
                        {
                            $( this ).html(jsonarray[id]['html']);
                        }
                    } );
                }
            }
        },
        lockbtn:function (status)
        {
            w.wpvividmu.media.lock=status;
        },
        islockbtn:function ()
        {
            return w.wpvividmu.media.lock;
        },
        restore_image:function()
        {
            if(w.wpvividmu.media.islockbtn())
            {
                return ;
            }
            w.wpvividmu.media.lockbtn(true);
            var id=$( this ).data( 'id' );
            var site_id=$( this ).data( 'site' );

            $( this ).addClass("button-disabled");

            var ajax_data = {
                'action': 'wpvivid_mu_restore_single_image',
                'id':id,
                'site_id':site_id
            };
            wpvivid_post_request(ajax_data, function(data)
            {
                w.wpvividmu.media.lockbtn(false);
                var jsonarray = jQuery.parseJSON(data);
                w.wpvividmu.media.update(jsonarray);

            }, function(XMLHttpRequest, textStatus, errorThrown)
            {
                w.wpvividmu.media.lockbtn(false);
                var error_message = wpvivid_output_ajaxerror('restore image', textStatus, errorThrown);
                alert(error_message);
            });
        },
        restore_image_edit:function ()
        {
            if(w.wpvividmu.media.islockbtn())
            {
                return ;
            }
            w.wpvividmu.media.lockbtn(true);
            var id=$( this ).data( 'id' );
            var site_id=$( this ).data( 'site' );

            $( this ).addClass("button-disabled");

            var ajax_data = {
                'action': 'wpvivid_mu_restore_single_image',
                'id':id,
                'site_id':site_id,
                'page':'edit'
            };
            wpvivid_post_request(ajax_data, function(data)
            {
                w.wpvividmu.media.lockbtn(false);
                var jsonarray = jQuery.parseJSON(data);
                w.wpvividmu.media.update(jsonarray,'edit');

            }, function(XMLHttpRequest, textStatus, errorThrown)
            {
                w.wpvividmu.media.lockbtn(false);
                var error_message = wpvivid_output_ajaxerror('restore image', textStatus, errorThrown);
                alert(error_message);
            });
        },
        restore_image_attachment:function ()
        {
            if(w.wpvividmu.media.islockbtn())
            {
                return ;
            }
            w.wpvividmu.media.lockbtn(true);
            var id=$( this ).data( 'id' );
            var site_id=$( this ).data( 'site' );

            $( this ).addClass("button-disabled");

            var ajax_data = {
                'action': 'wpvivid_mu_restore_single_image',
                'id':id,
                'site_id':site_id,
                'page':'attachment'
            };

            wpvivid_post_request(ajax_data, function(data)
            {
                w.wpvividmu.media.lockbtn(false);
                var jsonarray = jQuery.parseJSON(data);
                w.wpvividmu.media.update(jsonarray,'attachment');

            }, function(XMLHttpRequest, textStatus, errorThrown)
            {
                w.wpvividmu.media.lockbtn(false);
                var error_message = wpvivid_output_ajaxerror('restore image attachment', textStatus, errorThrown);
                alert(error_message);
            });
        },
        get_attachment_progress:function ()
        {
            $(this).find('.wpvivid-media-attachment').each(function()
            {
                var id=$(this).data( 'id' );
                alert(id);
            });

        }
    };
    w.wpvividmu.media.init();
})(jQuery, window);