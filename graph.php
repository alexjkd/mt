<?php
# PHPlot Demo
# 2009-12-01 ljb
# For more information see http://sourceforge.net/projects/phplot/

# Load the PHPlot class library:
require_once 'phplot.php';

# Define the data array: Label, the 3 data sets.
# Year,  Features, Bugs, Happy Users:
$data = array(
  array('2001',  60,  35,  20),
  array('2002',  65,  30,  30),
  array('2003',  70,  25,  40),
  array('2004',  72,  20,  60),
  array('2005',  75,  15,  70),
  array('2006',  77,  10,  80),
  array('2007',  80,   5,  90),
  array('2008',  85,   4,  95),
  array('2009',  90,   3,  98),
);

# Create a PHPlot object which will make an 800x400 pixel image:
$p = new PHPlot(800, 400);

# Use TrueType fonts:
$p->SetDefaultTTFont('./arial.ttf');

# Set the main plot title:
$p->SetTitle('PHPlot Customer Satisfaction (estimated)');

# Select the data array representation and store the data:
$p->SetDataType('text-data');
$p->SetDataValues($data);

# Select the plot type - bar chart:
$p->SetPlotType('bars');

# Define the data range. PHPlot can do this automatically, but not as well.
$p->SetPlotAreaWorld(0, 0, 9, 100);

# Select an overall image background color and another color under the plot:
$p->SetBackgroundColor('#ffffcc');
$p->SetDrawPlotAreaBackground(True);
$p->SetPlotBgColor('#ffffff');

# Draw lines on all 4 sides of the plot:
$p->SetPlotBorderType('full');

# Set a 3 line legend, and position it in the upper left corner:
$p->SetLegend(array('Features', 'Bugs', 'Happy Users'));
$p->SetLegendWorld(0.1, 95);

# Turn data labels on, and all ticks and tick labels off:
$p->SetXDataLabelPos('plotdown');
$p->SetXTickPos('none');
$p->SetXTickLabelPos('none');
$p->SetYTickPos('none');
$p->SetYTickLabelPos('none');

# Generate and output the graph now:
$p->DrawGraph();
?>