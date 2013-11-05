<?

class TopicDataFormatter {
	var $start;
	var $limit;
	var $totalTopics;
	var $topics;
	var $fmt_actions;

	function TopicDataFormatter($start, $limit, $topicsInfo) {
		$this->start = $start;
		$this->limit = $limit;
		$this->totalTopics = $topicsInfo['total'];
		$this->topics = $topicsInfo['data'];


		$this->fmt_actions = function($row, $index) use ($start) {
			return action_links(
				action_link("Edit", "pencil", "topicedit.php?topicid={$row[$index]}"),
				action_link("Delete", "cross", "topicdatamanager.php?topicid={$row[$index]}&delete&pagestart=$start","return confirm('". addslashes(_L('Are you sure you want to delete this topic?')) ."');"));
		};
	}

	public function anyTopics() {
		return count($this->topics);
	}

	public function showMenu() {
		showPageMenu($this->totalTopics, $this->start, $this->limit);
	}

	public function showTable() {
		showTable($this->topics,
							array("name" => "Name", "id" => "Action"),
							array("id" => $this->fmt_actions));

	}

}

?>