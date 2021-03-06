
SegmentFilter = window.SegmentFilter || {};

SegmentFilter.enabled = function() {
    return ReviewImproved.enabled();
}

if (SegmentFilter.enabled())
(function($, UI, SF, undefined) {

    var lastFilterData = null ;

    var keyForLocalStorage = function() {
        var page = ( config.isReview ? 'revise' : 'translate' );
        return 'SegmentFilter-' + page + '-' + config.id_job + '-' + config.password ;
    } ;

    $.extend(SF, {
        getLastFilterData : function() {
            return lastFilterData;
        },

        filterPanelOpen : function() {
            return UI.body.hasClass('filtering');
        },

        filtering : function() {
            // TODO change this, more specific when filter is submitted.
            return lastFilterData != null;
        },

        getStoredState : function() {
            var data = localStorage.getItem( keyForLocalStorage() ) ;
            if ( data ) {
                try {
                    return JSON.parse( data ) ;
                }
                catch( e ) {
                    this.clearStoredData();
                    console.error( e.message );
                }
            }
        },

        clearStoredData : function() {
            return localStorage.removeItem( keyForLocalStorage() ) ;
        },

        saveState : function( data ) {
            localStorage.setItem(keyForLocalStorage(), JSON.stringify(
                window.segment_filter_panel.state
            ) ) ;
        },

        restore : function( data ) {
            window.segment_filter_panel.setState( this.getStoredState() ) ;
            $(document).trigger('segment-filter-submit');
        },

        filterSubmit : function( data ) {
            data = { filter: data } ;

            var path = sprintf('/api/v2/jobs/%s/%s/segments-filter?%s',
                              config.id_job, config.password, $.param( data )
                              );

            return $.getJSON(path).pipe(function( data ) {
                $(document).trigger('segment-filter:filter-data:load', { data: data });

                lastFilterData = data;

                window.segment_filter_panel.setState({
                    filteredCount : data.count,
                    filtering : true
                });

                // TODO:
                //      UI.clearStorage('SegmentFilter') is needed to avoid bloating local storage.
                //      This prevents two filters on different tabs to persist on page reload:
                //      only the last one applied remains in localStorage.
                UI.clearStorage('SegmentFilter');

                SegmentFilter.saveState( window.segment_filter_panel.state ) ;

                $('#outer').empty();
                return UI.render({
                    segmentToOpen: data['segment_ids'][0]
                });
            })
        },

        openFilter : function() {
            UI.body.addClass('filtering');
            $(document).trigger('header-tool:open', { name: 'filter' });
        },

        closeFilter : function() {
            this.clearStoredData();

            UI.body.removeClass('filtering');
            $('.muted').removeClass('muted');
            lastFilterData = null;
            window.segment_filter_panel.resetState();
        }
    });

    $(document).on('ready', function() {
        // mount the hiddent react component by default so we can keep status
        window.segment_filter_panel = ReactDOM.render(
          React.createElement(
            SegmentFilter_MainPanel, {}),
            $('#segment-filter-mountpoint')[0]
          );
    });

    $(document).on('header-tool:open', function(e, data) {
        if ( data.name != 'filter' ) {
            SF.closeFilter();
        }
    });

    $(document).on('click', "header .filter", function(e) {
        e.preventDefault();

        if ( UI.body.hasClass('filtering') ) {
            SF.closeFilter();
        } else {
            SF.openFilter();
        }
    });


})(jQuery, UI, SegmentFilter);
