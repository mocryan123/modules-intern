// blocks/letterhead/index.js
( function( blocks, element ) {
    var el = element.createElement;

    blocks.registerBlockType( 'bae/letterhead', {
        edit: function() {
            return el( 'div', { style: { padding: '20px', background: '#f9fafb', border: '1px solid #e5e7eb', borderRadius: '8px' } },
                el( 'strong', {}, 'BAE — Letterhead' ),
                el( 'p', { style: { color: '#6b7280', fontSize: '13px', marginTop: '6px' } }, 'Renders your branded letterhead from your Brand Asset Engine profile.' )
            );
        },
        save: function() {
            return null; // server-side rendered
        }
    } );

} )( window.wp.blocks, window.wp.element );
