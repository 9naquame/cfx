/**
 * Main package for the entire WYF javascript file.
 */

var wyf =
{
    getMulti : function(params, callback)
    {
        $.getJSON('/system/api/get_multi?params=' + escape(JSON.stringify(params)),
            function(response){
                if(typeof callback === 'function') callback(response);
            }
        );
    },
    
    openWindow : function(location)
    {
        window.open(location);
    },

    showUploadedData : function(data)
    {
        $("#import-preview").html(data);
    },
    
    updateFilter: function(table, model, value)
    {
        if(value == 0)
        {
            externalConditions[table] = "";
        }
        else
        {
            externalConditions[table] = model + "= ?";
            externalBoundData = [value];
        }
        window[table + 'Search']();
    },

    confirmRedirect:function(message,path)
    {
        if(confirm(message))
        {
            document.location=path;
        }
    },
	
    init:function()
    {
        wyf.menus.init();
        wyf.tapi.init();
    },

    

    menus:
    {
        expand:function(id)
        {
            $("#"+id).slideToggle("fast", function(){
                if(typeof(Storage) !== "undefined") { 
                    var item = localStorage.getItem('menu_expansion');
                    if ($("#"+id).css("display") === 'block') {
                        item += id + ';';
                        localStorage.setItem('menu_expansion', item);
                    } else {
                        var regex = new RegExp(id + ';', "g");
                        localStorage.menu_expansion.replace(regex,'');
                        localStorage.setItem('menu_expansion', item.replace(regex,''));
                    }
                } else {
                    $.ajax({
                        url: "/vendor/9naquame/cfx/scripts/cfx.php",
                        type: "POST",
                        data: {
                            item:id+"="+$("#"+id).css("display"),
                            type: 'expandedMenus',
                            save: 'true'
                        },
                        success: function(values){  
                            menu_expand_content = $.parseJSON(values).split(";");
                        }
                    });
                }
            });
        },

        init:function()
        {
            if (typeof(Storage) !== "undefined" && localStorage.getItem('logged_in') !== null) {
                if (localStorage.getItem('menu_expansion') === null) {
                    localStorage.setItem('menu_expansion', '');
                } else {
                    wyf.menus.collapsible();
                }
            } else {
                if (typeof(Storage) !== "undefined") {
                    localStorage.setItem('logged_in', true);
                }
                $.ajax({
                    url: "/vendor/codogh/codolab/scripts/codo.php",
                    type: "POST",
                    data: {
                        type: 'expandedMenus',
                        save: 'false'
                    },
                    success: function(values){ 
                        localStorage.setItem('menu_expansion', $.parseJSON(values));
                        wyf.menus.collapsible();
                    }
                });
            }
        },
        
        collapsible: function()
        {
            menu_expand_content = localStorage.getItem('menu_expansion').split(";");
            for(var i = 0; i < menu_expand_content.length; i++){
                nv_pair = menu_expand_content[i];
                if(nv_pair.match("menu-")){
                    $("#"+nv_pair).attr("style","display:block");
                }
            }
        }
    },

    tapi:
    {	
        tables: new Object(),
        tableIds: new Array(),
        activity : null,
		
        addTable: function(id,obj)
        {
            wyf.tapi.tableIds.push(id);
            wyf.tapi.tables[id] = obj;
            wyf.tapi.tables[id].prevPage = 0;
        },
		
        init:function()
        {
            for(var i=0; i < wyf.tapi.tableIds.length; i++)
            {
                var id = wyf.tapi.tableIds[i];  
                //$("#"+id+">tbody").load(wyf.tapi.tables[id].path);
                wyf.tapi.render(wyf.tapi.tables[id]);
            }
        },
		
        render:function(table,action,params)
        {
            var urlParams = "params=" + escape(JSON.stringify(table));
            
            try{
                wyf.tapi.activity.abort();
            }
            catch(e)
            {
                
            }

            wyf.tapi.activity = $.ajax({
                type:"POST",
                url:table.url,
                dataType:"json",
                data:urlParams,
                success:function(r)
                {
                    $("#"+table.id+">tbody").html(r.tbody);
                    $("#"+table.id+"Footer").html(r.footer);
                    $('#'+table.id+"-operations").html(r.operations);
                }
            });
        },
		
        sort:function(id,field)
        {
            if(wyf.tapi.tables[id].sort == "ASC")
            {
                wyf.tapi.tables[id].sort = "DESC";
            }
            else
            {
                wyf.tapi.tables[id].sort = "ASC";
            }
			
            //$("#"+id+">tbody").load(wyf.tapi.tables[id].path+"&sort="+field+"&sort_type="+wyf.tapi.tables[id].sort);
            wyf.tapi.tables[id].sort_field[0].field = field;
            wyf.tapi.tables[id].sort_field[0].type = wyf.tapi.tables[id].sort;
            wyf.tapi.render(wyf.tapi.tables[id]);
        },
		
        switchPage:function(id,page)
        {
            var table = wyf.tapi.tables[id]; 
            table.page = page;
            wyf.tapi.render(table);
            $("#"+id+"-page-id-"+page).addClass("page-selected");
            $("#"+id+"-page-id-"+table.prevPage).removeClass("page-selected");
            table.prevPage = page;
        },
		
        showSearchArea:function(id)
        {
            $("#tapi-"+id+"-search").toggle();
        },
		
        checkToggle:function(id,checkbox)
        {
            $("."+id+"-checkbox").attr("checked", checkbox.checked);
        },
		
        remove:function(id)
        {
            var ids = new Array();
            if(confirm("Are you sure you want to delete the selected elements?"))
            {
                $("."+id+"-checkbox").each(
                    function()
                    {
                        if(this.checked)
                        {
                            ids.push(this.value);
                        }
                    }
                    );
                wyf.tapi.render(wyf.tapi.tables[id],"delete",JSON.stringify(ids));
            }
        },
		
        showOperations:function(tableId, id)
        {
            var offset = $('#'+tableId+'-operations-row-' + id).offset();
            var tableOffset = $('#' + tableId).offset();
            $(".operations-box").hide();
            
            $("#"+tableId+"-operations-box-" + id).css(
                {
                    left:((tableOffset.left) + $('#' + tableId).width() - ($("#"+tableId+"-operations-box-" + id).width() + 65))+'px',
                    top: (offset.top + 1) + 'px'
                }
            ).show();
			
        }
    }
};