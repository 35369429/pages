import React, {  Component } from 'react';
import { Dimensions,PixelRatio,ScrollView, View, Text, Image } from 'react-native';
import HTMLView from 'react-native-htmlview';
import { color, css, articleCss, articleOption} from 'skin/pages/ArticleDetail';
import mobx, { observable, computed } from "mobx";
import { observer } from 'mobx-react';
import Loader from '../../components/Loader';
import xpm from '../../xpm';

/**
 * Home 页面数据驱动
 */
class __data {

  @observable cates = [];
  @observable article = {};
  @observable loading = false;

  constructor() {
     // mobx.autorun(() => console.log('auto run', this.page));
  }

  reset() {
    this.loading = false;
  }

  setArticle( article ){
    this.article = article || {};
  }

  fetch( article_id  ) {
    this.loading = true;
    let $get = xpm.api('/xpmsns/pages/article/get');
    $get().get({articleId:article_id}).then(( article )=>{
      console.log( 'Detail onFetch', article );
      this.setArticle(article);
      this.loading  = false;
    }).catch( (excp) => {
      // 读取数据失败
      console.log( 'excp:',  excp );
    });
  }
}

let $data = new __data();
const dp2px = dp=>PixelRatio.getPixelSizeForLayoutSize(dp);
const px2dp = px=>PixelRatio.roundToNearestPixel(px);

function drawImageScaled( attr ) {
  attr = attr || {};
  let sc = Dimensions.get('window');
  let pxRatio = PixelRatio.get();
  let scale = 1/pxRatio;
  let img = {
    width: parseInt(attr['data-width'] ? attr['data-width'] : attr['width']),
    height:parseInt(attr['data-height'] ? attr['data-height'] : attr['height'])
  }
  if ( img.width == 0 ) {
    img.width = 1;
  }
  if ( img.height == 0)  {
     img.height = img.width;
  }

  let offset = articleOption.image.offset  /scale ;
  let h = img.height /scale;
  let w = img.width / scale;
  if ( w - offset > (sc.width/scale - offset)  ) {
      let ratio = attr['data-ratio'] ? parseInt('data-ratio') : parseInt(img.width) / parseInt(img.height);
      w = sc.width/scale - offset ;
      h = w / ratio;
  }

  if ( h == null ) { h = 100;}

  return {width:parseInt( w ), height:parseInt( h )};
}


@observer
export default class ArticleDetail extends React.Component {
  componentDidMount() {
    let params = this.props.navigation.state.params;
    // $data.setArticle(params);
    $data.fetch(params['article_id']);
  }

  _renderNode (node, index, siblings, parent, defaultRenderer) {
    if (node.name === 'img') {

      const a = node.attribs;
      const h = drawImageScaled(a).height;
      const w = drawImageScaled(a).width;

      if ( isNaN(h)|| isNaN(w) ) {
        return null;
      }

      let src = a['data-url'] ? a['data-url'] : a.src;
      console.log( src, a);
      return (
        <Image
          key={index}
          source={{uri: src}}
          resizeMode={'cover'}
          style={{
            width: drawImageScaled(a).width,
            height: drawImageScaled(a).height,
          }}
        />
      );
    }
  }

  render() {
    if ( $data.loading ) {
      return( <Loader /> );
    }
    let rs = $data.article;
    return(
      <ScrollView style={[css.wrapper]}>
          <View><Text style={[css.title]}>{rs.title}</Text></View>
          <View style={css.sub}>
            <Text style={[css.subItem]}>{rs.author}</Text>
            <Text style={[css.subItem]}>{rs.publish_date}</Text>
          </View>
          <HTMLView
            value={rs.content}
            renderNode={this._renderNode}
            stylesheet={articleCss}
          />
      </ScrollView>
    )
  }
}
