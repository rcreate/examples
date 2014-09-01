var utils = {
	price : {
		convertTo : function(stringVal) {
			if( typeof stringVal != "undefined" ) {
				var lastSeperatorIndex = Math.max( stringVal.lastIndexOf(','), stringVal.lastIndexOf('.') );
				var number;
				if( lastSeperatorIndex >= 0 ) {
					var dec = parseFloat('0.'+stringVal.substr(lastSeperatorIndex+1));
					var num = parseInt(stringVal.substr(0,lastSeperatorIndex).replace(/[\.,]/,''));
					if( isNaN(num) ) num = 0;
					if( isNaN(dec) ) dec = 0;
					number = num+dec;
				} else {
					number = parseFloat(stringVal);
				}
				return (isNaN(number) ? 0 : number);
			}
			return 0.00;
		},
		convertFrom : function(doubleVal, precision){
			precision = precision || 2;
			var tSep = ',',dSep='.';
			switch( tyState.lang ) {
				case 'de':
					tSep = '.'; dSep = ','; break;
			}
				doubleVal = doubleVal.toFixed(precision) + '';
			x = doubleVal.split('.');
			x1 = x[0];
			x2 = x.length > 1 ? dSep + x[1] : '';
			var rgx = /(\d+)(\d{3})/;
			while (rgx.test(x1)) {
				x1 = x1.replace(rgx, '$1' + tSep + '$2');
			}
			return x1 + x2;
		}
	},
	strToScope : function(string) {
		var scope = null;
		if( string.indexOf('.') > 0 ) {
			var parts = string.split('.');
			scope = window;
			for( var i in parts ) {
				if( typeof scope[ parts[i] ] != "undefined" ) {
					scope = scope[ parts[i] ];
				} else {
					break;
				}
			}
		} else {
			scope = window[ string ];
		}
		if( $.isFunction(scope) ) return scope;
		else return null;
	}
};