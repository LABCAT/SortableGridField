<?php
class GridFieldSortableRowsTest extends SapphireTest {

	/** @var ArrayList */
	protected $list;
	
	/** @var GridField */
	protected $gridField;
	
	/** @var Form */
	protected $form;
	
	/** @var string */
	public static $fixture_file = 'GridFieldSortableRowsTest.yml';

	/** @var array */
	protected $extraDataObjects = array('GridFieldAction_SortOrder_Team', 'GridFieldAction_SortOrder_VTeam');
	
	public function setUp() {
		parent::setUp();
		$this->list = GridFieldAction_SortOrder_Team::get();
		$config = GridFieldConfig::create()->addComponent(new GridFieldSortableRows('SortOrder'));
		$this->gridField = new GridField('testfield', 'testfield', $this->list, $config);
		$this->form = new Form(new Controller(), 'mockform', new FieldList(array($this->gridField)), new FieldList());
	}
	
	public function testSortActionWithoutCorrectPermission() {
		if(Member::currentUser()) { Member::currentUser()->logOut(); }
		$this->setExpectedException('ValidationException');
		$team1 = $this->objFromFixture('GridFieldAction_SortOrder_Team', 'team1');
		$team2 = $this->objFromFixture('GridFieldAction_SortOrder_Team', 'team2');
		$team3 = $this->objFromFixture('GridFieldAction_SortOrder_Team', 'team3');
		
		$stateID = 'testGridStateActionField';
		Session::set($stateID, array('grid'=>'', 'actionName'=>'saveGridRowSort', 'args'=>array('GridFieldSortableRows'=>array('sortableToggle'=>true))));
		$request = new SS_HTTPRequest('POST', 'url', array('ItemIDs'=>"$team1->ID, $team3->ID, $team2->ID"), array('action_gridFieldAlterAction?StateID='.$stateID=>true, $this->form->getSecurityToken()->getName()=>$this->form->getSecurityToken()->getValue()));
		$this->gridField->gridFieldAlterAction(array('StateID'=>$stateID), $this->form, $request);
		$this->assertEquals($team3->ID, $this->list->last()->ID, 'User should\'t be able to sort records without correct permissions.');
	}
	
	public function testSortActionWithAdminPermission() {
		$team1 = $this->objFromFixture('GridFieldAction_SortOrder_Team', 'team1');
		$team2 = $this->objFromFixture('GridFieldAction_SortOrder_Team', 'team2');
		$team3 = $this->objFromFixture('GridFieldAction_SortOrder_Team', 'team3');
		$this->logInWithPermission('ADMIN');
		$stateID = 'testGridStateActionField';
		Session::set($stateID, array('grid'=>'', 'actionName'=>'saveGridRowSort', 'args'=>array('GridFieldSortableRows'=>array('sortableToggle'=>true))));
		$request = new SS_HTTPRequest('POST', 'url', array('ItemIDs'=>"$team1->ID, $team3->ID, $team2->ID"), array('action_gridFieldAlterAction?StateID='.$stateID=>true, $this->form->getSecurityToken()->getName()=>$this->form->getSecurityToken()->getValue()));
		$this->gridField->gridFieldAlterAction(array('StateID'=>$stateID), $this->form, $request);
		$this->assertEquals($team2->ID, $this->list->last()->ID, 'User should be able to sort records with ADMIN permission.');
	}
	
	public function testSortActionVersioned() {
		//Force versioned to reset
		Versioned::reset();
		
		$list = GridFieldAction_SortOrder_VTeam::get();
		$this->gridField->setList($list);
		$this->gridField->getConfig()->getComponentByType('GridFieldSortableRows')->setUpdateVersionedStage('Live');
		
		//Publish all records
		foreach($list as $item) {
			$item->publish('Stage', 'Live');
		}
		
		$team1 = $this->objFromFixture('GridFieldAction_SortOrder_VTeam', 'team1');
		$team2 = $this->objFromFixture('GridFieldAction_SortOrder_VTeam', 'team2');
		$team3 = $this->objFromFixture('GridFieldAction_SortOrder_VTeam', 'team3');
		
		$this->logInWithPermission('ADMIN');
		$stateID = 'testGridStateActionField';
		Session::set($stateID, array('grid'=>'', 'actionName'=>'saveGridRowSort', 'args'=>array('GridFieldSortableRows'=>array('sortableToggle'=>true))));
		$request = new SS_HTTPRequest('POST', 'url', array('ItemIDs'=>"$team1->ID, $team3->ID, $team2->ID"), array('action_gridFieldAlterAction?StateID='.$stateID=>true, $this->form->getSecurityToken()->getName()=>$this->form->getSecurityToken()->getValue()));
		$this->gridField->gridFieldAlterAction(array('StateID'=>$stateID), $this->form, $request);
		
		$this->assertEquals($team2->ID, $list->last()->ID, 'Sort should have happened on Versioned stage "Stage"');
		
		$list=Versioned::get_by_stage('GridFieldAction_SortOrder_VTeam', 'Live');
		$this->assertEquals($team2->ID, $list->last()->ID, 'Sort should have happened on Versioned stage "Live"');
	}
}

class GridFieldAction_SortOrder_Team extends DataObject implements TestOnly {
	static $db = array(
		'Name' => 'Varchar',
		'City' => 'Varchar',
		'SortOrder' => 'Int'
	);
	
	static $default_sort='SortOrder';
}

class GridFieldAction_SortOrder_VTeam extends DataObject implements TestOnly {
	static $db = array(
		'Name' => 'Varchar',
		'City' => 'Varchar',
		'SortOrder' => 'Int'
	);
	
	static $default_sort='SortOrder';
	
	static $extensions=array(
		"Versioned('Stage', 'Live')"
	);
}
?>