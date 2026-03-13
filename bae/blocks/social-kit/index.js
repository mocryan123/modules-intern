( function( blocks, element ) {
    var el = element.createElement;
    blocks.registerBlockType( 'bae/social-kit', {
        edit: function() {
            return el( 'div', { style: { padding: '20px', background: '#f9fafb', border: '1px solid #e5e7eb', borderRadius: '8px' } },
                el( 'strong', {}, 'BAE — Social Kit' ),
                el( 'p', { style: { color: '#6b7280', fontSize: '13px', marginTop: '6px' } }, 'Renders your branded social media kit from your Brand Asset Engine profile.' )
            );
        },
        save: function() { return null; }
    } );
} )( window.wp.blocks, window.wp.element );