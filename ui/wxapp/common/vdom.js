/**
 * VDom To Wxapp
 */


class Vdom {
	
	constructor( options )  {
		options = options || {};
		this.options = options;
		this.document = null;
	}

	load( doc ) {
		if( typeof doc != 'object' ) {
			throw new Error("document 类型错误"); 
		}

		if( typeof doc['document'] != 'object' ) {
			throw new Error("document 格式错误"); 
		}

		this.document = doc['document'];

		this.each( this.document );
	}


	each( node, fn, depth = 0 ) {

		if ( typeof fn != 'function' ) {  
			fn = this.print
		}

		if ( typeof node != 'object' ) {
			return;
		}

		depth++;
		if ( depth > 1 ) {
			fn( node, depth);
		}
		if ( Array.isArray( node.children ) ) {
			
			for( let i=0; i<node.children.length; i++ ) {
				let child = node.children[i];
				this.each( child, fn, depth );
			}			
		} 

	}

	print( node, depth )  {
		console.log( 'render', node, depth );
	}
}


module.exports = Vdom;