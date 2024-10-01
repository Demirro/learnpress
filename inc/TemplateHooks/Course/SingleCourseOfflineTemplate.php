<?php
/**
 * Template hooks Single Course Offline.
 *
 * @since 4.2.7
 * @version 1.0.1
 */

namespace LearnPress\TemplateHooks\Course;

use LearnPress\Helpers\Config;
use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\CourseModel;
use LearnPress\Models\CoursePostModel;
use LearnPress\Models\UserModel;
use LearnPress\TemplateHooks\Instructor\SingleInstructorTemplate;
use LP_Course;
use LP_Datetime;
use Throwable;

class SingleCourseOfflineTemplate {
	use Singleton;

	/**
	 * @var SingleCourseTemplate
	 */
	public $singleCourseTemplate;

	public function init() {
		$this->singleCourseTemplate = SingleCourseTemplate::instance();

		add_action(
			'learn-press/single-course/offline/layout',
			[ $this, 'course_offline_layout' ]
		);
	}

	/**
	 * Offline course layout
	 *
	 * @param $course
	 *
	 * @return void
	 */
	public function course_offline_layout( $course ) {
		if ( ! $course instanceof CourseModel ) {
			return;
		}

		if ( ! $course->is_offline() ) {
			return;
		}

		$user = UserModel::find( get_current_user_id(), true );

		ob_start();
		learn_press_breadcrumb();
		$html_breadcrumb = ob_get_clean();

		// Author
		$singleInstructorTemplate = SingleInstructorTemplate::instance();
		$author                   = $course->get_author_model();
		$html_author              = '';
		if ( $author ) {
			$html_author = sprintf(
				'%s %s',
				__( 'By', 'learnpress' ),
				sprintf( '<a href="%s">%s</a>', $author->get_url_instructor(), $singleInstructorTemplate->html_display_name( $author ) )
			);
		}
		// End author

		// Instructor
		$html_instructor = '';
		if ( $author ) {
			$html_instructor_image   = sprintf(
				'<a href="%s" title="%s">%s</a>',
				$author->get_url_instructor(),
				$author->get_display_name(),
				$singleInstructorTemplate->html_avatar( $author )
			);
			$section_instructor_meta = [
				'wrapper'        => '<div class="lp-instructor-meta">',
				'count_students' => sprintf(
					'<div class="instructor-item-meta">%s</div>',
					$singleInstructorTemplate->html_count_students( $author )
				),
				'count_courses'  => sprintf(
					'<div class="instructor-item-meta">%s</div>',
					$singleInstructorTemplate->html_count_courses( $author )
				),
				'wrapper_end'    => '</div>',
			];
			$html_instructor_meta    = Template::combine_components( $section_instructor_meta );

			$section_instructor_right = apply_filters(
				'lean-press/single-course/offline/section-instructor/right',
				[
					'wrapper'     => '<div class="lp-section-instructor">',
					'name'        => $singleInstructorTemplate->html_display_name( $author ),
					'meta'        => $html_instructor_meta,
					'description' => $singleInstructorTemplate->html_description( $author ),
					'social'      => $singleInstructorTemplate->html_social( $author ),
					'wrapper_end' => '</div>',
				],
				$course,
				$user
			);
			$html_instructor_right    = Template::combine_components( $section_instructor_right );
			$section_instructor       = apply_filters(
				'lean-press/single-course/offline/section-instructor',
				[
					'wrapper'          => '<div class="lp-section-instructor">',
					'header'           => sprintf( '<h3>%s</h3>', __( 'Instructor', 'learnpress' ) ),
					'wrapper_info'     => '<div class="lp-instructor-info">',
					'image'            => $html_instructor_image,
					'instructor_right' => $html_instructor_right,
					'wrapper_info_end' => '</div>',
					'wrapper_end'      => '</div>',
				],
				$course,
				$user
			);
			$html_instructor          = Template::combine_components( $section_instructor );
		}
		// End instructor

		// Info one
		$html_address     = $this->singleCourseTemplate->html_address( $course );
		$section_info_one = apply_filters(
			'lean-press/single-course/offline/info-bar',
			[
				'wrapper'     => '<div class="lp-single-course-offline-info-one">',
				'author'      => sprintf( '<div class="item-meta">%s</div>', $html_author ),
				'address'     => ! empty( $html_address ) ? sprintf( '<div class="item-meta">%s</div>', $html_address ) : '',
				'wrapper_end' => '</div>',
			],
			$course,
			$user
		);

		// Section left
		$section_left = apply_filters(
			'learn-press/single-course/offline/section-left',
			[
				'wrapper'     => '<div class="lp-single-offline-course__left">',
				'breadcrumb'  => $html_breadcrumb,
				'title'       => $this->singleCourseTemplate->html_title( $course, 'h1' ),
				'info_one'    => Template::combine_components( $section_info_one ),
				'image'       => $this->singleCourseTemplate->html_image( $course ),
				'description' => $this->singleCourseTemplate->html_description( $course ),
				'box-extra'   => $this->html_box_extra( $course ),
				'instructor'  => $html_instructor,
				'wrapper_end' => '</div>',
			],
			$course,
			$user
		);

		// Section right
		// Info two
		$data_info_meta = [
			'price'        => [
				'label' => sprintf( '<span class="currency">%s</span> %s', learn_press_get_currency_symbol(), __( 'Price', 'learnpress' ) ),
				'value' => $this->singleCourseTemplate->html_price( $course ),
			],
			'deliver_type' => [
				'label' => sprintf( '<span class="lp-icon-bookmark-o"></span> %s', __( 'Delivery type', 'learnpress' ) ),
				'value' => $this->singleCourseTemplate->html_deliver_type( $course ),
			],
			'capacity'     => [
				'label' => sprintf( '<span class="lp-icon-students"></span> %s', __( 'Capacity', 'learnpress' ) ),
				'value' => $this->singleCourseTemplate->html_capacity( $course ),
			],
			'level'        => [
				'label' => sprintf( '<span class="lp-icon-signal"></span> %s', __( 'Level', 'learnpress' ) ),
				'value' => $this->singleCourseTemplate->html_level( $course ),
			],
			'duration'     => [
				'label' => sprintf( '<span class="lp-icon-clock-o"></span> %s', __( 'Duration', 'learnpress' ) ),
				'value' => $this->singleCourseTemplate->html_duration( $course ),
			],
		];

		$html_lesson = $this->html_lesson_info( $course );
		if ( ! empty( $html_lesson ) ) {
			$data_info_meta['lessons'] = [
				'label' => sprintf( '<span class="lp-icon-copy"></span> %s', __( 'Lessons', 'learnpress' ) ),
				'value' => $html_lesson,
			];
		}

		$data_info_meta = apply_filters( 'learn-press/single-course/offline/info-meta', $data_info_meta, $course, $user );
		$html_info_meta = '';
		if ( ! empty( $data_info_meta ) ) {
			foreach ( $data_info_meta as $info_meta ) {
				$label              = $info_meta['label'];
				$value              = $info_meta['value'];
				$html_info_two_item = sprintf(
					'<div class="info-meta-item">
						<span class="info-meta-left">%s</span>
						<span class="info-meta-right">%s</span>
					</div>',
					$label,
					$value
				);
				$html_info_meta    .= $html_info_two_item;
			}
		}

		$section_buttons = apply_filters(
			'learn-press/single-course/offline/section-right/info-meta/buttons',
			[
				'wrapper'     => '<div class="course-buttons">',
				'btn_contact' => $this->singleCourseTemplate->html_btn_external( $course ),
				'btn_buy'     => $this->singleCourseTemplate->html_btn_purchase_course( $course, $user ),
				'wrapper_end' => '</div>',
			],
			$course,
			$user
		);
		$html_buttons    = Template::combine_components( $section_buttons );

		$section_info_two = apply_filters(
			'learn-press/single-course/offline/section-right/info-meta',
			[
				'wrapper'     => '<div class="info-metas">',
				'meta'        => $html_info_meta,
				'buttons'     => $html_buttons,
				'wrapper_end' => '</div>',
			],
			$course,
			$user
		);
		$html_info_two    = Template::combine_components( $section_info_two );
		// End info two
		$section_right = apply_filters(
			'learn-press/single-course/offline/section-right',
			[
				'wrapper'         => '<div class="lp-single-offline-course__right">',
				'info_two'        => $html_info_two,
				'featured_review' => $this->singleCourseTemplate->html_feature_review( $course ),
				'sidebar'         => $this->singleCourseTemplate->html_sidebar( $course ),
				'wrapper_end'     => '</div>',
			],
			$course,
			$user
		);
		// End section right

		// Related courses
		ob_start();
		do_action( 'learn-press/single-course/courses-related/layout', $course, 4 );
		$html_courses_related = ob_get_clean();
		// End related courses

		$sections = [
			'wrapper'          => '<div class="lp-single-offline-course">',
			'wrapper_main'     => '<div class="lp-single-offline-course-main">',
			'section_left'     => Template::combine_components( $section_left ),
			'section_right'    => Template::combine_components( $section_right ),
			'wrapper_main_end' => '</div>',
			'related_courses'  => $html_courses_related,
			'wrapper_end'      => '</div>',
		];

		echo Template::combine_components( $sections );
	}

	/**
	 * Html lesson info
	 *
	 * @param CourseModel $course
	 *
	 * @return string
	 */
	public function html_lesson_info( CourseModel $course, $show_label = false ): string {
		$lesson_count = $course->get_meta_value_by_key( CoursePostModel::META_KEY_OFFLINE_LESSON_COUNT, 10 );

		if ( ! $lesson_count ) {
			return '';
		}

		$html = sprintf(
			'<span class="lesson-count">%s%s</span>',
			$lesson_count,
			$show_label ? ' ' . __( 'lessons', 'learnpress' ) : ''
		);

		return $html;
	}

	/**
	 * Box extra
	 *
	 * @param CourseModel $course
	 *
	 * @return string
	 * @since 4.2.7.2
	 * @version 1.0.0
	 */
	public function html_box_extra( CourseModel $course ): string {
		$section = apply_filters(
			'learn-press/single-course/offline/section-box-extra',
			[
				'wrapper'      => '<div class="course-box-extra-info">',
				'material'     => $this->singleCourseTemplate->html_material( $course ),
				'faqs'         => $this->singleCourseTemplate->html_faqs( $course ),
				'requirements' => $this->singleCourseTemplate->html_requirements( $course ),
				'features'     => $this->singleCourseTemplate->html_features( $course ),
				'target'       => $this->singleCourseTemplate->html_target( $course ),
				'wrapper_end'  => '</div>',
			],
			$course
		);

		return Template::combine_components( $section );
	}
}
