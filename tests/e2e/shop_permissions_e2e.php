<?php
//End-to-end check of per-shop permissions against a running copy of the application - by
//default the local XAMPP site:
//
//   C:/xampp/php/php.exe tests/e2e/shop_permissions_e2e.php [base-url]
//
//The admin signs in to the e2e Warehouse and changes what e2e Store Keeper may do THERE. The
//e2e Showroom must keep the ticks it had, and the two people who hold that same role - alice
//in the warehouse, bob in the showroom - must end up with different menus. Like the other E2E
//scripts it creates its own e2e records and removes them at the end. Run it only against a
//development database.
require __DIR__ . '/lib.php';

$pdo = (new E2EDb())->pdo();
$fx = new E2EFixtures($pdo);
$fx->up();
$pw = $fx->password;
$W = $fx->shops['W'];
$S = $fx->shops['S'];
$keeper = $fx->roles['keeper'];

//the editor form of Public/edit-role.php: every module and every feature is posted, ticked or
//not. $ticks is [feature id => ['view', 'edit', ...]], $modules the menus to show.
function roleForm(PDO $pdo, $role_id, $role_name, array $modules, array $ticks, $csrf)
{
    $form = ['edit-role' => 'Submit', 'role_id' => $role_id, 'role_name' => $role_name,
        'csrf_token' => $csrf, 'module_id' => [], 'module_user' => [], 'feature_id' => []];

    foreach ($pdo->query("SELECT SMID FROM sysmodules ORDER BY sort_order")->fetchAll(PDO::FETCH_COLUMN) as $module) {
        $module = (int) $module;
        $form['module_id'][] = $module;
        if (in_array($module, $modules, true)) {
            $form['module_user'][$module] = [$module];
        }//this menu is shown

        $features = $pdo->prepare("SELECT SFID FROM sysfeatures WHERE SystemModules_SMID = ? AND SFID NOT IN (20, 54) ORDER BY sort_order");
        $features->execute([$module]);
        foreach ($features->fetchAll(PDO::FETCH_COLUMN) as $feature) {
            $feature = (int) $feature;
            $form['feature_id'][$module][] = $feature;
            foreach (isset($ticks[$feature]) ? $ticks[$feature] : [] as $right) {
                $form[$right][$module][$feature] = ['on'];
            }//each right ticked
        }//each feature
    }//each module

    return $form;
}//roleForm

try {
    $admin = new E2EBrowser($base);

    echo "The role editor works in the shop it is open in\n";
    signIn($admin, 'e2e_admin', $pw);
    shopLogin($admin, $W, 'e2e_admin', $pw);
    $admin->get('Public/user-roles.php');
    $token = $admin->csrf();
    check('the role list names the shop it is showing', $admin->isOn('Public/user-roles.php')
        && $admin->has('Features in e2e Warehouse') && $admin->has('Menus in e2e Warehouse'), $admin);
    check('and shows what the role may do here', $admin->has('Store-view'), $admin);
    checkClean('the role list', $admin);

    $admin->get('Public/edit-role.php?id=' . $keeper);
    check('the editor says which shop it is ticking', $admin->has('may do in <b>e2e Warehouse</b>'), $admin);
    checkClean('the role editor', $admin);

    echo "A change in the warehouse stays in the warehouse\n";
    $before = $fx->ticksOf($keeper, $S);
    //the warehouse keeper stops looking after the Store and takes over Invoice List (feature 56)
    $form = roleForm($pdo, $keeper, 'e2e Store Keeper', [2], [56 => ['view', 'print']], $token);
    $admin->post('Controller/userrolecontrol.php', $form);
    check('the warehouse ticks are what was saved', $fx->ticksOf($keeper, $W) === [56 => 'vp'], $admin);
    check('the warehouse menus are what was saved', $fx->modulesOf($keeper, $W) === [2], $admin);
    check('the showroom ticks are untouched', $fx->ticksOf($keeper, $S) === $before && $before === [1 => 'v'], $admin);
    check('and so are the showroom menus', $fx->modulesOf($keeper, $S) === [1], $admin);

    $admin->post('Controller/userrolecontrol.php', roleForm($pdo, $keeper, 'e2e Store Keeper', [1], [1 => ['view']], str_repeat('0', 64)));
    check('a forged form changes nothing', $fx->ticksOf($keeper, $W) === [56 => 'vp'], $admin);

    echo "The same role, two shops, two menus\n";
    $alice = new E2EBrowser($base);
    signIn($alice, 'e2e_alice', $pw);
    shopLogin($alice, $W, 'e2e_alice', $pw);
    $alice->get('Public/home.php');
    check('in the warehouse the keeper has lost the Store menu', !sees($alice, 'store.php'), $alice);
    check('and has the Invoice List the warehouse gave her', sees($alice, 'invoice-list.php'), $alice);
    checkClean('the warehouse home page', $alice);
    $alice->get('Public/store.php');
    check('the page she may no longer see sends her home', $alice->has('window.location.href = "./home.php"'), $alice);

    $bob = new E2EBrowser($base);
    signIn($bob, 'e2e_bob', $pw);
    shopLogin($bob, $S, 'e2e_bob', $pw);
    $bob->get('Public/home.php');
    check('in the showroom the same role still has the Store menu', sees($bob, 'store.php'), $bob);
    check('and not the warehouse\'s Invoice List', !sees($bob, 'invoice-list.php'), $bob);
    checkClean('the showroom home page', $bob);
    $bob->get('Public/store.php');
    check('and he may still open it', $bob->isOn('Public/store.php')
        && !$bob->has('window.location.href = "./home.php"'), $bob);

    echo "A role with nothing ticked here\n";
    $admin->post('Controller/userrolecontrol.php', roleForm($pdo, $keeper, 'e2e Store Keeper', [], [], $token));
    check('the ticks of this shop can all be taken away', $fx->ticksOf($keeper, $W) === [] && $fx->modulesOf($keeper, $W) === [], $admin);
    check('without touching the other shop', $fx->ticksOf($keeper, $S) === [1 => 'v'], $admin);
    $admin->get('Public/user-roles.php');
    check('the role list says so instead of showing an empty row', $admin->has('Nothing ticked here') && $admin->has('No menu here'), $admin);
    checkClean('the role list with an empty role', $admin);

    $alice->get('Public/home.php');
    check('the keeper may still enter the warehouse, with no menu of her own', $alice->isOn('Public/home.php')
        && !sees($alice, 'store.php') && !sees($alice, 'invoice-list.php'), $alice);
    checkClean('the warehouse home page of a role with nothing ticked', $alice);

    //scenarios of later tasks are added above this line
} finally {
    $fx->down();
}

echo $failures === 0 ? "\nAll checks passed.\n" : "\n" . $failures . " check(s) FAILED.\n";
exit($failures === 0 ? 0 : 1);
