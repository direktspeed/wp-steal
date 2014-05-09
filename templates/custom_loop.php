<?php if( $include_filter === 'true' ): ?>
  <div id="filter_wrapper">
    <a class="btn btn_show_filter clearfix" href="#"><span>Filter</span></a>
    <div id="df_sidebar_filters_wrap" style="visibility:hidden;">
      <div id="df_sidebar_filters"></div>
    </div>
  </div>
<?php endif; ?>

<?php if( $type == 'hdp_event' ): ?>
  <?php if( $do_shortcode === true ): ?>
    <div id="hdp_filter_events" class="hdp_filter">
      <div id="hdp_filter_event" class="hdp_filter_event_past hidden-phone">
        <span class="hdp_filter_text">Display:</span>
        <div class="df_element df_sortable_button df_sortable_upcoming df_sortable_active" _filter="upcoming">Upcoming</div>
        <div class="df_element df_sortable_button df_sortable_past" _filter="past">Past</div>
        <div class="df_element df_sortable_button df_sortable_all" _filter="all">All</div>
      </div>
    </div>
    <div id="hdp_sort_event" class="hdp_sort clearfix">
      <div id="hdp_results_sorter" class="hdp_results_sorter_distance"><span class="hdp_sort_text">Sort By:</span></div> <!-- Generated by DF -->
    </div>
  <?php endif; ?>

  <ul id="hdp_results_header_event" class="hdp_results_header clearfix">
    <li class="hdp_event_time">Date</li>
    <li class="hdp_event_name">Name</li>
    <li class="hdp_event_city">City</li>
    <li class="hdp_event_state">State</li>
  </ul>
<?php endif; ?>

<div id="dynamic_filter" class="dynamic_filter df_element df_top_wrapper df_element df_top_wrapper clearfix" dynamic_filter="<?php echo $type; ?>">
  <div class="df_element hdp_results clearfix">
    <ul class="df_element hdp_results_items"> <?php
      if( function_exists( 'woo_loop_before' ) ) {
        woo_loop_before();
      }

      /** Lets go ahead and build our table if we don't have it */
      //Flawless_F::update_qa_table( $type );

      //echo $query_array[ $type ];
      $res = $wpdb->get_results( $query_array[ $type ] );
      $total = $wpdb->get_col( "SELECT FOUND_ROWS();" );
      if( $res && is_array( $res ) && count( $res ) ) {
        foreach( $res as $row ) {
          $post = json_decode( $row->object, true ); ?>
          <li class="hdp_results_item" id="df_id_<?php echo $post[ 'ID' ]; ?>">
            <ul class="df_result_data">
              <li attribute_key="raw_html">
                <ul> <?php
                  $count++;
                  get_template_part( 'loop', $type ); ?>
                </ul>
              </li>
            </ul>
          </li> <?php
        }
      }
      if( function_exists( 'woo_loop_after' ) ) {
        woo_loop_after();
      }
      ?>
    </ul> <?php

    /** If we're on the hope page, and we need to show the link to show more events */
    if( is_front_page() && $total ) {
      ?>
      <div class="hdp_results_message" style="display: block;"><div class="df_load_status"><span>Displaying <?php echo $per_page; ?></span> of <?php echo $total[ 0 ]; ?>
          Events <a class="btn" href="/events"><span>Show All</span></a></div></div> <?php
    } ?>

  </div>
</div> <!-- Overwritten by DF -->
