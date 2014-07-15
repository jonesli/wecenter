<?php
/*
+--------------------------------------------------------------------------
|   WeCenter [#RELEASE_VERSION#]
|   ========================================
|   by WeCenter Software
|   © 2011 - 2013 WeCenter. All Rights Reserved
|   http://www.wecenter.com
|   ========================================
|   Support: WeCenter@qq.com
|
+---------------------------------------------------------------------------
*/


if (!defined('IN_ANWSION'))
{
	die;
}

class topic extends AWS_ADMIN_CONTROLLER
{
	public function setup()
	{
		TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(303));
	}

	public function list_action()
	{
		$this->crumb(AWS_APP::lang()->_t('话题管理'), 'admin/topic/list/');

		if ($_POST)
		{
			foreach ($_POST as $key => $val)
			{
				if ($key == 'keyword')
				{
					$val = rawurlencode($val);
				}

				$param[] = $key . '-' . $val;
			}

			H::ajax_json_output(AWS_APP::RSM(array(
				'url' => get_js_url('/admin/topic/list/' . implode('__', $param))
			), 1, null));
		}

		$where = array();

		if ($_GET['keyword'])
		{
			$where[] = "topic_title LIKE '" . $this->model('topic')->quote($_GET['keyword']) . "%'";
		}

		if ($_GET['question_count_min'] OR $_GET['question_count_min'] == '0')
		{
			$where[] = 'discuss_count >= ' . intval($_GET['question_count_min']);
		}

		if ($_GET['question_count_max'] OR $_GET['question_count_max'] == '0')
		{
			$where[] = 'discuss_count <= ' . intval($_GET['question_count_max']);
		}

		if (base64_decode($_GET['start_date']))
		{
			$where[] = 'add_time >= ' . strtotime(base64_decode($_GET['start_date']));
		}

		if (base64_decode($_GET['end_date']))
		{
			$where[] = 'add_time <= ' . strtotime('+1 day', strtotime(base64_decode($_GET['end_date'])));
		}

		$topic_list = $this->model('topic')->get_topic_list(implode(' AND ', $where), 'topic_id DESC', $this->per_page, $_GET['page']);

		$total_rows = $this->model('topic')->found_rows();

		if ($topic_list)
		{
			foreach ($topic_list AS $key => $topic_info)
			{
				$action_log = ACTION_LOG::get_action_by_event_id($topic_info['topic_id'], 1, ACTION_LOG::CATEGORY_TOPIC, implode(',', array(
					ACTION_LOG::ADD_TOPIC,
					ACTION_LOG::MOD_TOPIC,
					ACTION_LOG::MOD_TOPIC_DESCRI,
					ACTION_LOG::MOD_TOPIC_PIC,
					ACTION_LOG::DELETE_TOPIC,
					ACTION_LOG::ADD_RELATED_TOPIC,
					ACTION_LOG::DELETE_RELATED_TOPIC
				)), -1);

				$action_log = $action_log[0];

				$topic_list[$key]['last_edited_uid'] = $action_log['uid'];

				$topic_list[$key]['last_edited_time'] = $action_log['add_time'];

				$last_edited_uids[] = $topic_list[$key]['last_edited_uid'];
				
				if ($topic_info['parent_id'])
				{
					$parent_ids[$topic_info['parent_id']] = $topic_info['parent_id'];
				}
			}
			
			if ($parent_ids)
			{
				$parent_topics_info = $this->model('topic')->get_topics_by_ids($parent_ids);
			}

			$users_info_query = $this->model('account')->get_user_info_by_uids($last_edited_uids);

			foreach ($users_info_query AS $user_info)
			{
				$users_info[$user_info['uid']] = $user_info;
			}
		}

		$url_param = array();

		foreach($_GET AS $key => $val)
		{
			if (!in_array($key, array('app', 'c', 'act', 'page')))
			{
				$url_param[] = $key . '-' . $val;
			}
		}

		TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
			'base_url' => get_js_url('/admin/topic/list/' . implode('__', $url_param)),
			'total_rows' => $total_rows,
			'per_page' => $this->per_page
		))->create_links());

		TPL::assign('topics_count', $total_rows);
		TPL::assign('list', $topic_list);
		TPL::assign('users_info', $users_info);
		TPL::assign('parent_topics_info', $parent_topics_info);
		

		TPL::output('admin/topic/list');
	}

	public function parent_action()
	{
		$this->crumb(AWS_APP::lang()->_t('根话题'), 'admin/topic/parent/');

		$topic_list = $this->model('topic')->get_topic_list('is_parent = 1', 'topic_id DESC', $this->per_page, $_GET['page']);

		$total_rows = $this->model('topic')->found_rows();

		TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
			'base_url' => get_js_url('/admin/topic/parent/'),
			'total_rows' => $total_rows,
			'per_page' => $this->per_page
		))->create_links());

		TPL::assign('list', $topic_list);

		TPL::output('admin/topic/parent');
	}

	public function edit_action()
	{
		$this->crumb(AWS_APP::lang()->_t('话题编辑'), 'admin/topic/edit/');

		if (!$topic_info = $this->model('topic')->get_topic_by_id($_GET['topic_id']))
		{
			H::redirect_msg(AWS_APP::lang()->_t('话题不存在'));
		}

		TPL::assign('topic_info', $topic_info);

		TPL::import_js('js/ajaxupload.js');

		TPL::output('admin/topic/edit');
	}
}