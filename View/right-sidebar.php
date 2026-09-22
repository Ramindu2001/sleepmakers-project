<?php 
$shopObj=new Shop();
$hasPrescription=$shopObj->hasPrescription($shop_id);
$user_id = $_SESSION['user_id'];
$user = $userObj->getOneUser($user_id);
$userType = $user[0]['UserType'];
//a super admin is not limited by roles and keeps their own; everyone else gets the role they
//hold in the current shop (Model/shop_access_class.php) - 0 matches no rights
$userRole_id = $userType == 1 ? $user[0]['UserRoles_URID'] : (int) (new ShopAccess())->getShopRoleId($user_id, $shop_id);
$counterObj = new Counter();
$counterData = $counterObj->getCounterByUserID($user_id,$shop_id);
?>
<div class="d-fixed" id="right-sidebar">
    <div>
        <div class="sidebar-nav scroll-sidebar">
            <ul id="sidebarnav">
                <li class="sidebar-item">
                    <?php 

                        if($shopObj->hasRetailShop($shop_id))
                        {
                            ?>
                    <a class="sidebar-link" href="../Public/gui-pos.php" aria-expanded="false">
                        <span class="d-flex">
                            <img src="../Assets/Images/icons/cashier.png" alt="" class="menu-icon">
                        </span>
                        <span class="hide-menu">Add POS</span>
                    </a>
                    <?php 
                        }
                        else
                        {

                        ?>
                    <a class="sidebar-link" href="../Public/wholesale-invoice.php" aria-expanded="false">
                        <span class="d-flex">
                            <img src="../Assets/Images/icons/cashier.png" alt="" class="menu-icon">
                        </span>
                        <span class="hide-menu">Add POS</span>
                        </a>
                    <?php 
                    }
                    ?>
                </li>
                <?php 
                $counterObj = new Counter();
                
                // echo $user_id."<br>";
                // echo $shop_id."<br>";
                if($shopObj->hascounter($shop_id)==1)
                {
                    $counterData = $counterObj->getCounterByUserID($user_id,$shop_id);
                    if(empty($counterData))
                    {
                        ?> 
                        <li class="sidebar-item">
                            <a class="sidebar-link" id="btn_new_counter"  href="javascript:void(0)" aria-expanded="false">
                                <span class="d-flex">
                                <img src="../Assets/Images/icons/plus.png" alt="" class="menu-icon">
                                </span>
                                <span class="hide-menu">New Counter</span>
                            </a>
                        </li>
                        <?php 
                    }
                    else
                    {
                        ?>
                        <li class="sidebar-item">
                            <a class="sidebar-link" id="btn_close_counter" href="javascript:void(0)" aria-expanded="false">
                                <span class="d-flex">
                                <img src="../Assets/Images/icons/close.png" alt="" class="menu-icon">
                                </span>
                                <span class="hide-menu">Close Counter</span>
                            </a>
                        </li>
                        <?php 
                    }
                }
                else
                {

                }
                ?>
                    <li class="sidebar-item">
                        <a class="sidebar-link" href="javascript:void" id="openDelivery" aria-expanded="false">
                            <span class="d-flex">
                                <img src="../Assets/Images/icons/deliverynote.png" alt="" class="menu-icon">
                            </span>
                            <span class="hide-menu" style="display: inline;">Delivery Note</span>
                        </a>
                    </li>
                    <?php
                    if($userType==1)
                    {
                        ?>
                            <li class="sidebar-item">
                                <a class="sidebar-link" href="../Public/credit-customers.php" aria-expanded="false">
                                    <span class="d-flex">
                                        <img src="../Assets/Images/icons/report.png" alt="" class="menu-icon">
                                    </span>
                                    <span class="hide-menu">Invoice Report</span>
                                </a>
                            </li>
                            <li class="sidebar-item">
                                <a class="sidebar-link" href="../Public/credit-customers.php" aria-expanded="false">
                                    <span class="d-flex">
                                        <img src="../Assets/Images/icons/customerdue.png" alt="" class="menu-icon">
                                    </span>
                                    <span class="hide-menu">Customer Due</span>
                                </a>
                            </li>
                            <?php 
                            $date=date("Y-m-d");
                            $script="?fdate=$date&tdate=$date";
                            ?>
                            <li class="sidebar-item">
                                <a class="sidebar-link" href="../Public/invoice-list.php<?=$script?>" aria-expanded="false">
                                <span class="d-flex">
                                    <img src="../Assets/Images/icons/report.png" alt="" class="menu-icon">
                                </span>
                                <span class="hide-menu" style="display: inline;">Invoice List</span>
                                </a>
                            </li>
                            <li class="sidebar-item">
                                <a class="sidebar-link" href="../Reports/customer-profiles.php" aria-expanded="false">
                                    <span class="d-flex">
                                        <img src="../Assets/Images/icons/personal.png" alt="" class="menu-icon">
                                    </span>
                                    <span class="hide-menu" >Customer Profile</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                <a class="sidebar-link" href="../Reports/due-sale.php" aria-expanded="false">
                                    <span class="d-flex">
                                        <img src="../Assets/Images/icons/due-date.png" alt="" class="menu-icon">
                                    </span>
                                    <span class="hide-menu">Due Sale</span>
                                </a>
                        
                            <?php
                            if($hasPrescription==1)
                            {
                                ?>
                                <li class="sidebar-item">
                                <a class="sidebar-link" id="add-prescription" href="javascript:void(0)" aria-expanded="false">
                                    <span class="d-flex">
                                        <img src="../Assets/Images/icons/pres.png" alt="" class="menu-icon">
                                    </span>
                                    <span class="hide-menu">Add Prescription</span>
                                </a>
                            </li>
                            <?php 
                            }
                    }
                    else
                    { 
                        $feature_id=39;
                        $feature=$userObj->getUserRoleFeatureAccess($userRole_id,$feature_id);
                        if ($feature[0]["is_view"]==1)  {
                            ?>
                            <li class="sidebar-item">
                            <a class="sidebar-link" href="../Reports/rpt_invoice_sales.php" aria-expanded="false">
                            <span class="d-flex">
                                    <img src="../Assets/Images/icons/report.png" alt="" class="menu-icon">
                                </span>
                                <span class="hide-menu">Invoice Report</span>
                                </a>
                                </li>
                            <?php
                        }
                            $feature_id=56;
                            $feature=$userObj->getUserRoleFeatureAccess($userRole_id,$feature_id);
                            if ($feature[0]["is_view"]==1) {
                                $date=date("Y-m-d");
                                $script="?fdate=$date&tdate=$date";
                                ?>
                                <li class="sidebar-item">
                                <a class="sidebar-link" href="../Public/invoice-list.php<?=$script?>" aria-expanded="false">
                                    <span class="d-flex">
                                        <img src="../Assets/Images/icons/report.png" alt="" class="menu-icon">
                                    </span>
                                    <span class="hide-menu" style="display: inline;">Invoice List</span>
                                    </a>
                                </li>
                                <?php
                            } 
                        ?>
                         <?php
                            $feature_id=9;
                            $feature=$userObj->getUserRoleFeatureAccess($userRole_id,$feature_id);
                            if ($feature[0]["is_view"]==1) 
                            {
                                ?>
                                <li class="sidebar-item">
                                <a class="sidebar-link" href="../Public/credit-customers.php" aria-expanded="false">
                                    <span class="d-flex">
                                        <img src="../Assets/Images/icons/customerdue.png" alt="" class="menu-icon">
                                    </span>
                                    <span class="hide-menu">Customer Due</span>
                                    </a>
                                </li>
                                <?php
                            } 
                        ?>
                        <?php
                            $feature_id=43;
                            $feature=$userObj->getUserRoleFeatureAccess($userRole_id,$feature_id);
                            if ($feature[0]["is_view"]==1) 
                            {
                                ?>
                                <li class="sidebar-item">
                                <a class="sidebar-link" href="../Reports/customer-profiles.php" aria-expanded="false">
                                    <span class="d-flex">
                                        <img src="../Assets/Images/icons/personal.png" alt="" class="menu-icon">
                                    </span>
                                    <span class="hide-menu">Customer Profile</span>
                                    </a>
                                </li>
                                <?php
                            } 
                        ?>
                        <?php
                            $feature_id=46;
                            $feature=$userObj->getUserRoleFeatureAccess($userRole_id,$feature_id);
                            if ($feature[0]["is_view"]==1)  {
                                ?>
                                <li class="sidebar-item">
                                <a class="sidebar-link" href="../Reports/due-sale.php" aria-expanded="false">
                                    <span class="d-flex">
                                        <img src="../Assets/Images/icons/due-date.png" alt="" class="menu-icon">
                                    </span>
                                    <span class="hide-menu">Due Sale</span>
                                </a>
                                </li>
                             <?php
                            } 
                        ?>
                    <?php 
                        if($hasPrescription==1)
                        {
                                ?>
                            <li class="sidebar-item">
                                <a class="sidebar-link" id="add-prescription" href="javascript:void(0)" aria-expanded="false">
                                    <span class="d-flex">
                                        <img src="../Assets/Images/icons/pres.png" alt="" class="menu-icon">
                                    </span>
                                    <span class="hide-menu">Add Prescription</span>
                                </a>
                            </li>
                            <?php 
                        }
                    }
                ?>
            </ul>
        </div>
    </div>
</div>
<?php
include '../View/modals/close-counter.php';
include '../View/modals/open-counter.php';
?>