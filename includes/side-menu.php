<div class="side-menu animate-dropdown outer-bottom-xs">
    <div class="head maizehub-category-head" style="background:linear-gradient(135deg,#dff0d6,#f8fbf2) !important;color:#255b38 !important;border-bottom:1px solid #cfe3c5;"><i class="icon fa fa-align-justify fa-fw" style="color:#078f76 !important;"></i> Categories</div>
    <nav class="yamm megamenu-horizontal" role="navigation">
  
        <ul class="nav">
              <?php $sql=mysqli_query($con,"select id,categoryName  from category");
while($row=mysqli_fetch_array($sql))
{
    ?>
            <li class="dropdown menu-item">
                <a href="category.php?cid=<?php echo $row['id'];?>" class="dropdown-toggle"><i class="icon fa fa-desktop fa-fw"></i>
                <?php echo $row['categoryName'];?></a>
            </li>
                <?php }?>
                        
</ul>
    </nav>
</div>
