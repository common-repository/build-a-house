<tr id="expence-<?php echo esc_attr( $args['ID'] ); ?>">
	<td class="number"><?php echo esc_html( $args['i'] ); ?></td>
	<td class="expence-title"><?php echo esc_html( $args['title'] ); ?></td>
	<td class="expence-breakdown"><?php echo get_the_term_list( $args['ID'], 'iworks_build_a_house_breakdown', '', ', ' ); ?></td>
	<td class="date"><?php echo esc_html( $args['date_start'] ); ?></td>
	<td class="number money"><?php echo esc_html( $args['cost'] ); ?></td>
</tr>

