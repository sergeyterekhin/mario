$.fn.liTranslit = function(options){
    // настройки по умолчанию
    var o = $.extend({
        elName: '#Title',
        elAlias: '#StaticPath',
        table: ''
    },options);
    return this.each(function(){
        var elName = $(this).find(o.elName),
            elAlias = $(this).find(o.elAlias),
            nameVal;
        function tr(el){
            nameVal = el.val();
            if(el.val()!=""){
            	validate(get_trans(nameVal));
            }
        };
        elName.change(function () {
      		tr($(this));
        });
        function get_trans() {
            en_to_ru = {
                'ый': 'iy',
            	'а': 'a',
                'б': 'b',
                'в': 'v',
                'г': 'g',
                'д': 'd',
                'е': 'e',
                'ё': 'e',
                'ж': 'zh',
                'з': 'z',
                'и': 'i',
                'й': 'y',
                'к': 'k',
                'л': 'l',
                'м': 'm',
                'н': 'n',
                'о': 'o',
                'п': 'p',
                'р': 'r',
                'с': 's',
                'т': 't',
                'у': 'u',
                'ф': 'f',
                'х': 'h',
                'ц': 'ts',
                'ч': 'ch',
                'ш': 'sh',
                'щ': 'shch',
                'ъ': '',
                'ы': 'y',
                'ь': '',
                'э': 'e',
                'ю': 'yu',
                'я': 'ya',
                ' ': '-',
                '!': '',
                '@': '',
                '#': '',
                '$': '',
                '%': '',
                '^': '',
                '&': '',
                '*': '',
                '(': '',
                ')': '',
                '"': '',
                '№': '',
                ';': '',
                '%': '',
                ':': '',
                '?': '',
                '\\': '',
                '/': '',
                '+': '',
                '_': '-',
                '`': '',
                '~': '',
                ',': ''
            };
            nameVal = nameVal.toLowerCase();
            nameVal = trim(nameVal);
            var trans = nameVal;
            for (key in en_to_ru) {
            	trans = trans.replace(new RegExp(escapeRegExp(key), "ig"), en_to_ru[key]);
            };
            trans = trans.replace(/\./g, "");
            trans = trans.replace(/[-]{2,}/g, "-");
            trans = trans.replace(/^-/g, "");
            trans = trans.replace(/-$/g, "");
            return trans;
        }
        
		function validate(staticpath){
			var newpath = "";
			$.ajax({
				url:ADMIN_PATH+"ajax.php",
		        type:"POST",
		        data:{
		      	  "Action":"ValidateStaticPath",
		      	  "StaticPath":staticpath,
		      	  "Table": o.table
		        },
		          dataType:"JSON",
		            success:function(data){
		            	if(typeof data.SessionExpired != 'undefined')
		          		{
		          			window.location.href = ADMIN_PATH+"index.php";
		          			return;
		          		}
		            	if(data && data.ValidStaticPath){
		            		elAlias.val(data.ValidStaticPath);
		          		}else{
		          			alert('error');
		          		}
		            }
		    });
        }

        function trim(string) {
            string = string.replace(/'|"|<|>|\!|\||@|#|$|%|^|\^|\$|\\|\/|&|\*|\(|\)|=|\|\/|;|\+|№|,|\?|_|:|{|}|~|`|\[|\]/g, "");
            string = string.replace(/(^\s+)|(\s+$)/g, "");
            return string;
        };
        
        function escapeRegExp(str) {
        	return str.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, "\\$&");
    	}
    });
};