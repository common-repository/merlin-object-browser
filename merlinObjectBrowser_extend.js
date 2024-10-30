//Copyright 2017  MerlinOne Inc.
// custom state : this controller contains your application logic
wp.media.controller.Custom = wp.media.controller.State.extend({

    initialize: function(){
    },
    
    // called each time the model changes
    refresh: function() {
        // update the toolbar
    	this.frame.toolbar.get().refresh();
	},
	
	// called when the toolbar button is clicked
	customAction: function(){
		var something ="";
	}
    
});

// custom toolbar : contains the buttons at the bottom
wp.media.view.Toolbar.Custom = wp.media.view.Toolbar.extend({
	initialize: function() {
		_.defaults( this.options, {
		    event: 'MOB_goToLibrary',
		    close: false,
			items: {
			    MOB_goToLibrary: {
			        text: wp.media.view.l10n.MOB_custom_button, // added via 'media_view_strings' filter,
			        style: 'primary',
			        priority: 80,
			        requires: false,
		    		id: 'MOB_goToLibrary',
			        click: this.customAction
			    }
			}
		});

		wp.media.view.Toolbar.prototype.initialize.apply( this, arguments );
	},

    // called each time the model changes
	refresh: function() {
	    // call the parent refresh
		wp.media.view.Toolbar.prototype.refresh.apply( this, arguments );
	},
	
	// triggered when the button is clicked
	customAction: function(){
		this.controller.state().customAction( this.controller );
		//update featured image view, then switch to library view
		this.controller.setState( 'featured-image' );
		if ( null !== wp.media.frame.content.get() ) {
	      wp.media.frame.content.get().collection.props.set( {ignore: (+ new Date())} );
	      wp.media.frame.content.get().options.selection.reset();
	    } else {
	      wp.media.frame.library.props.set( {ignore: (+ new Date())} );
	    }

        // switch to the library view
        this.controller.setState( 'insert' );
        if ( null !== wp.media.frame.content.get() ) {
	      wp.media.frame.content.get().collection.props.set( {ignore: (+ new Date())} );
	      wp.media.frame.content.get().options.selection.reset();
	    } else {
	      wp.media.frame.library.props.set( {ignore: (+ new Date())} );
	    }
	}
});

// custom content : this view contains the main panel UI
wp.media.view.Custom = wp.media.View.extend({
	className: 'media-MOB_custom',
	
	// bind view events
	events: {
		'input': 'custom_update',
		'keyup': 'custom_update',
		'change': 'custom_update'
	},

	initialize: function() {

		var frameContent = '';
		if ( typeof merlinObjectBrowser_merlinUrl != 'string' || '' == merlinObjectBrowser_merlinUrl)
		{
			frameContent = 'Merlin archive url not configured. Check settings and then try again.';
		}
		else
		{
			var buildFrameContent = jQuery("<div>");
			var fullContent = jQuery( '<span />' );
			fullContent.attr( 'id', 'merlinObjectBrowser2_message' );
			buildFrameContent.append(fullContent);
			var iFrame = jQuery( '<iframe />' )
			iFrame.attr( 'id', 'merlinObjectBrowser2_int' );
			iFrame.attr( 'src', merlinObjectBrowser_merlinUrl );
			iFrame.css( 'width', '100%' );
			iFrame.css( 'height', 'calc(100% - 5px)' );
			buildFrameContent.append(iFrame);
			frameContent = buildFrameContent.html();
		}

		// insert it in the view
	    this.$el.append(frameContent);
	},
	
	render: function(){
	    return this;
	},
	
	custom_update: function( event ) {
	}
});

// supersede the default MediaFrame.Post view
var oldMediaFrame = wp.media.view.MediaFrame.Post;
wp.media.view.MediaFrame.Post = oldMediaFrame.extend({

    initialize: function() {
        oldMediaFrame.prototype.initialize.apply( this, arguments );
        
        this.states.add([
            new wp.media.controller.Custom({
                id:         'MOB_id',
                menu:       'default', // menu event = menu:render:default
                content:    'MOB_custom',
				title:      wp.media.view.l10n.MOB_custom_menu_title, // added via 'media_view_strings' filter
				priority:   200,
				toolbar:    'MOB_library', // toolbar event = toolbar:create:main-my-action
				type:       'link'
            })
        ]);

        this.on( 'content:render:MOB_custom', this.customContent, this );
        this.on( 'toolbar:create:MOB_library', this.createCustomToolbar, this );
        this.on( 'toolbar:render:MOB_library', this.renderCustomToolbar, this );
    },
    
    createCustomToolbar: function(toolbar){
        toolbar.view = new wp.media.view.Toolbar.Custom({
		    controller: this
	    });
    },

    customContent: function(){
        
        // this view has no router
        this.$el.addClass('hide-router');

        // custom content view
        var view = new wp.media.view.Custom({
            controller: this,
            model: this.state().props
        });

        this.content.set( view );
    }

});
