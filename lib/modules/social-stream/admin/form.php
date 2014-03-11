<p>
  <label for="<?php echo $this->get_field_id( 'wall' ); ?>"><?php _e( 'Use Wall' ) ?></label>
  <select id="<?php echo $this->get_field_id( 'wall' ); ?>" name="<?php echo $this->get_field_name( 'wall' ); ?>" style="width:100%;">
    <option value="false" <?php selected( 'false', $instance[ 'wall' ], true ); ?>>No</option>
    <option value="true" <?php selected( 'true', $instance[ 'wall' ], true ); ?>>Yes</option>
  </select>
</p>

<p>
  <label for="<?php echo $this->get_field_id( 'rotate_delay' ); ?>"><?php _e( 'Rotate Delay (ms)' ) ?></label>
  <input type="text" value="<?php echo !empty( $instance[ 'rotate_delay' ] )?$instance[ 'rotate_delay' ]:''; ?>" id="<?php echo $this->get_field_id( 'rotate_delay' ); ?>" name="<?php echo $this->get_field_name( 'rotate_delay' ); ?>" />
</p>